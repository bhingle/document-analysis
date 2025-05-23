<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;


/* 
    Controller for authentication of user
    Developer - Abhishek Bhingle
*/
class AuthenticatedSessionController extends Controller
{
    // Handle an incoming authentication request.
    public function store(LoginRequest $request): JsonResponse
    {
        $request->authenticate();

        $request->session()->regenerate();
        
        Log::info('Login sucessful');

        return response()->json([
            'message' => 'Login successful.',
        ], 200);
    }

    // Destroy an authenticated session.
    public function destroy(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        Log::info('Logout sucessful');

        return response()->json([
            'message' => 'Logout successful!',
        ], 200);
    }
}
