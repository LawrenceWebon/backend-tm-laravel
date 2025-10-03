<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Handle user registration
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Create a token for the user with 10 minute expiration
        $token = $user->createToken('auth_token', ['*'], now()->addMinutes(10))->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
            'message' => 'User registered successfully',
        ], 201);
    }

    /**
     * Handle user login
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $user = User::where('email', $request->email)->firstOrFail();
        $token = $user->createToken('auth_token', ['*'], now()->addMinutes(10))->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ]);
    }

    /**
     * Handle user logout
     */
    public function logout(Request $request)
    {
        // For stateful authentication (cookies), we don't need to delete tokens
        // The session will be invalidated automatically
        if ($request->user()->currentAccessToken()) {
            // Only delete if it's a personal access token (not transient)
            if (method_exists($request->user()->currentAccessToken(), 'delete')) {
                $request->user()->currentAccessToken()->delete();
            }
        }

        // Invalidate the session for stateful authentication
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Logged out successfully']);
    }

    /**
     * Get the authenticated user
     */
    public function me(Request $request)
    {
        return response()->json($request->user());
    }

    /**
     * Refresh user token (called when user is active)
     */
    public function refreshToken(Request $request)
    {
        $user = $request->user();
        
        // Delete current token
        $user->currentAccessToken()->delete();
        
        // Create new token with 10 minute expiration
        $token = $user->createToken('auth_token', ['*'], now()->addMinutes(10))->plainTextToken;
        
        return response()->json([
            'user' => $user,
            'token' => $token,
            'message' => 'Token refreshed successfully',
        ]);
    }
}
