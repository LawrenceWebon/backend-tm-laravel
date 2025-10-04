<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
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

        // Create a token for the user with 30 minute expiration
        $token = $user->createToken('auth_token', ['*'], now()->addMinutes(30))->plainTextToken;

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

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $user->createToken('auth_token', ['*'], now()->addMinutes(30))->plainTextToken;

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
        // Delete the current access token if it's a persistent token
        $token = $request->user()->currentAccessToken();

        if ($token && method_exists($token, 'delete')) {
            $token->delete();
        }

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
        $token = $user->currentAccessToken();
        if ($token && method_exists($token, 'delete')) {
            $token->delete();
        }

        // Create new token with 30-minute expiration
        $token = $user->createToken('auth_token', ['*'], now()->addMinutes(30))->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
            'message' => 'Token refreshed successfully',
        ]);
    }
}
