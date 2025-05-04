<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\DocumentController;
use Illuminate\Support\Facades\Log;
use Illuminate\Session\Middleware\StartSession;
use Barryvdh\DomPDF\Facade\Pdf;

/*
    API endpoints and there navigation to respective controllers.
    Developer - Abhishek Bhingle
*/
Route::middleware([StartSession::class])->group(function () {
    // API Endpoints for registration, login and logout
    Route::post('/register', [RegisteredUserController::class, 'store'])->middleware('guest')->name('register');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('guest')->name('login');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->middleware('auth')->name('logout');

    // Upload the document
    Route::middleware('auth')->group(function () {
        Route::post('/documents', [DocumentController::class, 'store']);
    });
    // Get the documents
    Route::middleware('auth')->get('/documents', [DocumentController::class, 'index']);
    // Delete the document
    Route::middleware('auth')->delete('/documents/{document}', [DocumentController::class, 'destroy']);
    // Download the document
    Route::middleware('auth')->get('/documents/{document}/download', [DocumentController::class, 'download']);

    // Analyze a specific document
    // Rate Limiting - throttle request,minute i.e. here 2 request per 5 min
    Route::middleware(['auth', 'throttle:2,5'])->post('/documents/{document}/analyze', [DocumentController::class, 'analyze']);

    // Get the details of the analyzed documents
    Route::middleware('auth')->get('/analyzed-documents', [DocumentController::class, 'analyzedDocuments']); 
});


Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});