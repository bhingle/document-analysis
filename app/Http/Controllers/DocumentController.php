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
        
        $filePath = storage_path('app/private/' . $document->filename);

        if (!file_exists($filePath)) {
            Log::error('File not found at path: ' . $filePath);
            return response()->json(['message' => 'File not found.'], 404);
        }

        // ✅ Properly extract text from PDF
        $parser = new Parser();
        $pdf = $parser->parseFile($filePath);
        $text = $pdf->getText();

        // 🔥 Fix encoding issues (still good practice)
        $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');

        Log::info('Extracted text from PDF:', ['text' => $text]);

        // HuggingFace API setup
        $huggingFaceApiKey = env('HUGGINGFACE_API_KEY');
        $huggingFaceModel = 'facebook/bart-large-mnli'; // or whatever model you want
#langchain
#nossl
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $huggingFaceApiKey,
        ])->post("https://api-inference.huggingface.co/models/{$huggingFaceModel}", [
            'inputs' => $text,
        ]);
        

        // Log::info('HuggingFace Response:', ['response' => $response->json()]);

        // if ($response->failed()) {
        //     return response()->json(['message' => 'Error analyzing document.'], 500);
        // }

        // Handle possible loading response
        // $attempts = 0;
        // while (($response->json() === null || isset($response->json()['error'])) && $attempts < 3) {
        //     Log::info('Model still loading... retrying');
        //     sleep(5); // Wait for 3 seconds
        //     $response = Http::withOptions([
        //         'verify' => false,
        //     ])->withHeaders([
        //         'Authorization' => 'Bearer ' . $huggingFaceApiKey,
        //     ])->post("https://api-inference.huggingface.co/models/{$huggingFaceModel}", [
        //         'inputs' => $text,
        //     ]);
        //     $attempts++;
        // }

        Log::info('Final HuggingFace Response:', ['response' => $response->json()]);

        if ($response->failed() || $response->json() === null) {
            return response()->json(['message' => 'Error analyzing document.'], 500);
        }


        $result = $response->json();

        return response()->json([
            'message' => 'Analysis successful.',
            'analysis' => $result,
        ]);
    }
    
}
