<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
}
