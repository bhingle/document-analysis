<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\DocumentController;
use Illuminate\Support\Facades\Log;


use Illuminate\Session\Middleware\StartSession; // 🛠️ Important

Route::middleware([StartSession::class])->group(function () {
    Route::post('/register', [RegisteredUserController::class, 'store'])->middleware('guest')->name('register');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('guest')->name('login');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->middleware('auth')->name('logout');
    Route::middleware('auth')->group(function () {
        Route::post('/documents', [DocumentController::class, 'store']);
    });
    Route::middleware('auth')->get('/documents', [DocumentController::class, 'index']);
    Route::middleware('auth')->delete('/documents/{document}', [DocumentController::class, 'destroy']);
    Route::middleware('auth')->get('/documents/{document}/download', [DocumentController::class, 'download']);
    Log::info('testing logs');
    // Analyze a specific document
    //Route::post('/documents/{document}/analyze', [DocumentController::class, 'analyze']);
    //Rate Limiting - throttle req,min i.e. here 1 req per 1 min
    Route::middleware(['auth', 'throttle:1,1'])->post('/documents/{document}/analyze', [DocumentController::class, 'analyze']);

    Route::middleware('auth')->get('/analyzed-documents', [DocumentController::class, 'analyzedDocuments']);



    

    
});


Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});