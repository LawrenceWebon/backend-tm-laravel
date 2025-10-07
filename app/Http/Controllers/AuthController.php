<?php

namespace App\Http\Controllers;

use App\Mail\ResetPasswordMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
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
        // Delete the current access token
        $token = $request->user()->currentAccessToken();

        if ($token) {
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

    /**
     * Send password reset link
     */
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user) {
            return response()->json([
                'message' => 'If an account with that email exists, we have sent a password reset link.',
            ], 200);
        }

        // Generate a password reset token
        $token = Str::random(64);

        // Store the token in the password_reset_tokens table
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            [
                'email' => $user->email,
                'token' => Hash::make($token),
                'created_at' => now(),
            ]
        );

        // Send the password reset email
        try {
            Mail::to($user->email)->send(new ResetPasswordMail($user, $token));

            return response()->json([
                'message' => 'If an account with that email exists, we have sent a password reset link.',
            ], 200);
        } catch (\Exception $e) {
            Log::error('Password reset email failed: '.$e->getMessage());

            return response()->json([
                'message' => 'If an account with that email exists, we have sent a password reset link.',
            ], 200);
        }
    }

    /**
     * Reset password
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'token' => 'required|string',
            'password' => 'required|string|min:6|confirmed',
        ]);

        // Find the password reset record
        $passwordReset = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (! $passwordReset) {
            return response()->json([
                'message' => 'Invalid or expired reset token.',
            ], 400);
        }

        // Check if token is valid and not expired (60 minutes)
        if (! Hash::check($request->token, $passwordReset->token)) {
            return response()->json([
                'message' => 'Invalid or expired reset token.',
            ], 400);
        }

        // Check if token is expired (60 minutes)
        $createdAt = \Carbon\Carbon::parse($passwordReset->created_at);
        $minutesDiff = $createdAt->diffInMinutes(now());

        if ($minutesDiff > 60) {
            // Delete expired token
            DB::table('password_reset_tokens')
                ->where('email', $request->email)
                ->delete();

            return response()->json([
                'message' => 'Invalid or expired reset token.',
            ], 400);
        }

        // Find the user and update password
        $user = User::where('email', $request->email)->first();
        if (! $user) {
            return response()->json([
                'message' => 'User not found.',
            ], 404);
        }

        // Update the user's password
        $user->password = Hash::make($request->password);
        $user->save();

        // Delete the password reset token
        DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->delete();

        // Revoke all existing tokens for security
        $user->tokens()->delete();

        return response()->json([
            'message' => 'Password has been reset successfully.',
        ], 200);
    }
}
