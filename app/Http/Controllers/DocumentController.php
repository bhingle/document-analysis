<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Smalot\PdfParser\Parser;


class DocumentController extends Controller
{
    /**
     * Handle document upload.
     */
    public function store(Request $request)
    {
        // Validate the request
        $request->validate([
            'file' => 'required|file|mimes:pdf,doc,docx|max:20480', // 20MB max
        ]);

        // Save the file
        $uploadedFile = $request->file('file');
        $storedFilename = $uploadedFile->store('documents');

        // Save info to database
        $document = Document::create([
            'user_id' => auth()->id(),
            'filename' => $storedFilename,
            'original_name' => $uploadedFile->getClientOriginalName(),
            'status' => 'pending', 
        ]);

        return response()->json([
            'message' => 'Document uploaded successfully!',
            'document' => $document,
        ], 201);
    }
    //Fetching all documents for respective users
    public function index(Request $request)
    {
        $documents = $request->user()->documents()->get()->map(function ($document) {
            return [
                'id' => $document->id,
                'original_name' => $document->original_name,
                'uploaded_at' => $document->created_at,
                'url' => Storage::url($document->filename),
            ];
        });

        return response()->json([
            'documents' => $documents,
        ]);
    }

    public function destroy(Request $request, Document $document)
    {
        
        // Authorize: user can only delete their own document
        if ($document->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        // Remove the document analysis from cache
        cache()->forget('document_analysis_' . $document->id);

        // Delete file from storage
        if (Storage::exists($document->filename)) {
            Storage::delete($document->filename);
        }

        // Delete record from database
        $document->delete();

        return response()->json(['message' => 'Document deleted successfully.']);
    }

    public function download($id)
    {
        $document = Document::findOrFail($id);
    
        $filePath = storage_path('app/private/documents/' . basename($document->filename));
    
        if (!file_exists($filePath)) {
            return response()->json([
                'message' => 'File not found.'
            ], 404);
        }
    
        return response()->download($filePath, $document->original_name);
    }

    public function analyze(Document $document)
    {
        Log::info('Inside analyze function');
    
        $cacheKey = 'document_analysis_' . $document->id;
    
        //  Check if cached result exists
        if (cache()->has($cacheKey)) {
            Log::info('Returning cached analysis result');
            return response()->json([
                'message' => 'Analysis fetched from cache.',
                'result' => cache()->get($cacheKey),
            ]);
        }
    
        // 🔍 Proceed to analyze if no cache
        $filePath = storage_path('app/private/' . $document->filename);
    
        if (!file_exists($filePath)) {
            return response()->json(['message' => 'File not found.'], 404);
        }
    
        // Extract PDF text
        $parser = new \Smalot\PdfParser\Parser();
        $pdf = $parser->parseFile($filePath);
        $text = $pdf->getText();
        $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
    
        // Call OpenAI API
        $openaiApiKey = env('OPENAI_API_KEY');
        $prompt = "You are an AI legal assistant. Analyze the following document and extract:\n
        1. Key Sections\n
        2. Critical Items\n
        3. Defined Terms\n
        4. Obligations\n
        \nDocument Text:\n\"$text\"";
    
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $openaiApiKey,
            'Content-Type' => 'application/json',
        ])->post('https://api.openai.com/v1/chat/completions', [
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                ['role' => 'system', 'content' => 'You are a helpful legal analysis assistant.'],
                ['role' => 'user', 'content' => $prompt],
            ],
            'temperature' => 0.3,
        ]);
    
        Log::info('Open AI raw response body:', ['body' => $response->body()]);
        Log::info('Open AI response JSON:', ['json' => $response->json()]);
    
        if ($response->failed()) {
            return response()->json(['message' => 'OpenAI analysis failed.'], 500);
        }
    
        $result = $response->json();
    
        // Store analysis result in cache for 1 hr (3600 seconds)
        cache()->put($cacheKey, $result, 3600);
    
        return response()->json([
            'message' => 'Analysis complete.',
            'result' => $result,
        ]);
    }
    

    public function analyzedDocuments(Request $request)
    {
        $user = $request->user();

        if ($user->role === 'admin') {
            // Admin: return all analyzed documents
            $documents = Document::whereNotNull('analysis')
                ->get(['id', 'original_name', 'analysis', 'created_at']);
        } else {
            // Customer: return only their own analyzed documents
            $documents = Document::where('user_id', $user->id)
                ->whereNotNull('analysis')
                ->get(['id', 'original_name', 'analysis', 'created_at']);
        }

        return response()->json([
            'analyzed_documents' => $documents
        ]);
    }


    
}
