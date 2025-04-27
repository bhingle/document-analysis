<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Http\JsonResponse;

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
}
