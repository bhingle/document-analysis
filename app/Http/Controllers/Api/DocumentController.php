<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Document;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class DocumentController extends Controller
{
    // public function upload(Request $request)
    // {
    //     $request->validate([
    //         'document' => 'required|file|mimes:pdf,txt|max:20480',
    //     ]);

    //     $file = $request->file('document');
    //     $path = $file->store('documents');

    //     $document = Document::create([
    //         'user_id' => Auth::id(),
    //         'filename' => $file->getClientOriginalName(),
    //         'filepath' => $path,
    //         'status' => 'pending',
    //     ]);

    //     return response()->json([
    //         'message' => 'Document uploaded successfully.',
    //         'document' => $document
    //     ]);
    // }

    // public function list()
    // {
    //     $documents = Auth::user()->documents()->latest()->get();
    //     return response()->json($documents);
    // }
}
