<?php

use App\Mail\ResetPasswordMail;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('forgot password endpoint can be accessed', function () {
    $response = $this->postJson('/api/forgot-password', [
        'email' => 'nonexistent@example.com',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['email']);
});

test('forgot password sends email for existing user', function () {
    Mail::fake();

    $user = User::factory()->create([
        'email' => 'test@example.com',
    ]);

    $response = $this->postJson('/api/forgot-password', [
        'email' => 'test@example.com',
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'message' => 'If an account with that email exists, we have sent a password reset link.',
    ]);

    Mail::assertSent(ResetPasswordMail::class, function ($mail) use ($user) {
        return $mail->user->id === $user->id;
    });
});

test('forgot password creates password reset token', function () {
    Mail::fake();

    $user = User::factory()->create([
        'email' => 'test@example.com',
    ]);

    $this->postJson('/api/forgot-password', [
        'email' => 'test@example.com',
    ]);

    $this->assertDatabaseHas('password_reset_tokens', [
        'email' => 'test@example.com',
    ]);
});

test('forgot password requires valid email', function () {
    $response = $this->postJson('/api/forgot-password', [
        'email' => 'invalid-email',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['email']);
});

test('forgot password requires email field', function () {
    $response = $this->postJson('/api/forgot-password', []);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['email']);
});

test('reset password endpoint can be accessed', function () {
    $response = $this->postJson('/api/reset-password', [
        'email' => 'nonexistent@example.com',
        'token' => 'test-token',
        'password' => 'newpassword',
        'password_confirmation' => 'newpassword',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['email']);
});

test('reset password requires all fields', function () {
    $response = $this->postJson('/api/reset-password', []);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['email', 'token', 'password']);
});

test('reset password requires password confirmation', function () {
    $response = $this->postJson('/api/reset-password', [
        'email' => 'test@example.com',
        'token' => 'test-token',
        'password' => 'newpassword',
        'password_confirmation' => 'differentpassword',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['password']);
});

test('reset password works with valid token', function () {
    Mail::fake();

    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('oldpassword'),
    ]);

    // First, request password reset
    $forgotResponse = $this->postJson('/api/forgot-password', [
        'email' => 'test@example.com',
    ]);

    $forgotResponse->assertStatus(200);

    // Get the token from the database
    $passwordReset = DB::table('password_reset_tokens')
        ->where('email', 'test@example.com')
        ->first();

    $this->assertNotNull($passwordReset);

    // Find the original token (we need to reverse engineer it from the hashed version)
    // Since we can't easily get the original token, we'll create a new one for testing
    $token = 'test-reset-token';
    DB::table('password_reset_tokens')
        ->where('email', 'test@example.com')
        ->update(['token' => Hash::make($token)]);

    // Now test the reset
    $response = $this->postJson('/api/reset-password', [
        'email' => 'test@example.com',
        'token' => $token,
        'password' => 'newpassword',
        'password_confirmation' => 'newpassword',
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'message' => 'Password has been reset successfully.',
    ]);

    // Verify password was changed
    $user->refresh();
    $this->assertTrue(Hash::check('newpassword', $user->password));

    // Verify token was deleted
    $this->assertDatabaseMissing('password_reset_tokens', [
        'email' => 'test@example.com',
    ]);
});

test('reset password fails with invalid token', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
    ]);

    $response = $this->postJson('/api/reset-password', [
        'email' => 'test@example.com',
        'token' => 'invalid-token',
        'password' => 'newpassword',
        'password_confirmation' => 'newpassword',
    ]);

    $response->assertStatus(400);
    $response->assertJson([
        'message' => 'Invalid or expired reset token.',
    ]);
});

test('reset password fails with expired token', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
    ]);

    // Create an expired token (older than 60 minutes)
    $token = 'test-token';
    DB::table('password_reset_tokens')->insert([
        'email' => 'test@example.com',
        'token' => Hash::make($token),
        'created_at' => now()->subHours(2), // 2 hours ago
    ]);

    $response = $this->postJson('/api/reset-password', [
        'email' => 'test@example.com',
        'token' => $token,
        'password' => 'newpassword',
        'password_confirmation' => 'newpassword',
    ]);

    $response->assertStatus(400);
    $response->assertJson([
        'message' => 'Invalid or expired reset token.',
    ]);

    // Verify expired token was deleted
    $this->assertDatabaseMissing('password_reset_tokens', [
        'email' => 'test@example.com',
    ]);
});

test('reset password requires minimum password length', function () {
    $response = $this->postJson('/api/reset-password', [
        'email' => 'test@example.com',
        'token' => 'test-token',
        'password' => '12345', // Too short
        'password_confirmation' => '12345',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['password']);
});
