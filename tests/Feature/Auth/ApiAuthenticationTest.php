<?php

use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

describe('API Authentication', function () {

    describe('Login API', function () {

        test('user can login with valid credentials', function () {
            $user = User::factory()->create([
                'email' => 'test@example.com',
                'password' => bcrypt('password123'),
            ]);

            $response = $this->postJson('/api/login', [
                'email' => 'test@example.com',
                'password' => 'password123',
            ]);

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'email_verified_at',
                        'created_at',
                        'updated_at',
                    ],
                    'token',
                ])
                ->assertJson([
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                    ],
                ]);

            // Verify token was created
            $this->assertDatabaseHas('personal_access_tokens', [
                'tokenable_type' => User::class,
                'tokenable_id' => $user->id,
            ]);

            // Verify token has 30-minute expiration
            $tokenRecord = \Laravel\Sanctum\PersonalAccessToken::where('tokenable_id', $user->id)->first();
            $this->assertNotNull($tokenRecord);
            $this->assertNotNull($tokenRecord->expires_at);
            $this->assertTrue($tokenRecord->expires_at->isFuture());
            $this->assertTrue($tokenRecord->expires_at->diffInMinutes(now()) <= 30);
        });

        test('user cannot login with invalid email', function () {
            User::factory()->create([
                'email' => 'test@example.com',
                'password' => bcrypt('password123'),
            ]);

            $response = $this->postJson('/api/login', [
                'email' => 'wrong@example.com',
                'password' => 'password123',
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['email']);
        });

        test('user cannot login with invalid password', function () {
            User::factory()->create([
                'email' => 'test@example.com',
                'password' => bcrypt('password123'),
            ]);

            $response = $this->postJson('/api/login', [
                'email' => 'test@example.com',
                'password' => 'wrongpassword',
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['email']);
        });

        test('login requires email field', function () {
            $response = $this->postJson('/api/login', [
                'password' => 'password123',
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['email']);
        });

        test('login requires password field', function () {
            $response = $this->postJson('/api/login', [
                'email' => 'test@example.com',
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['password']);
        });

        test('login requires valid email format', function () {
            $response = $this->postJson('/api/login', [
                'email' => 'invalid-email',
                'password' => 'password123',
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['email']);
        });
    });

    describe('Logout API', function () {

        test('authenticated user can logout', function () {
            $user = User::factory()->create();
            $token = $user->createToken('test-token')->plainTextToken;

            $response = $this->withHeaders([
                'Authorization' => 'Bearer '.$token,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Content-Length' => '0',
            ])->postJson('/api/logout', []);

            $response->assertStatus(200)
                ->assertJson([
                    'message' => 'Logged out successfully',
                ]);

            // Verify token was deleted
            $this->assertDatabaseMissing('personal_access_tokens', [
                'tokenable_type' => User::class,
                'tokenable_id' => $user->id,
            ]);
        });

        test('unauthenticated user cannot logout', function () {
            $response = $this->postJson('/api/logout', []);

            $response->assertStatus(401)
                ->assertJson([
                    'message' => 'Unauthenticated.',
                ]);
        });

        test('logout with invalid token returns 401', function () {
            $response = $this->withHeaders([
                'Authorization' => 'Bearer invalid-token',
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Content-Length' => '0',
            ])->postJson('/api/logout', []);

            $response->assertStatus(401)
                ->assertJson([
                    'message' => 'Unauthenticated.',
                ]);
        });

        test('logout deletes only the current token', function () {
            $user = User::factory()->create();
            $token1 = $user->createToken('test-token-1')->plainTextToken;
            $token2 = $user->createToken('test-token-2')->plainTextToken;

            // Verify both tokens exist
            $this->assertDatabaseCount('personal_access_tokens', 2);

            // Logout with first token
            $response = $this->withHeaders([
                'Authorization' => 'Bearer '.$token1,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Content-Length' => '0',
            ])->postJson('/api/logout', []);

            $response->assertStatus(200);

            // Verify only one token remains
            $this->assertDatabaseCount('personal_access_tokens', 1);
            $this->assertDatabaseHas('personal_access_tokens', [
                'tokenable_type' => User::class,
                'tokenable_id' => $user->id,
            ]);
        });
    });

    describe('User API', function () {

        test('authenticated user can get their profile', function () {
            $user = User::factory()->create();
            $token = $user->createToken('test-token')->plainTextToken;

            $response = $this->withHeaders([
                'Authorization' => 'Bearer '.$token,
                'Accept' => 'application/json',
            ])->getJson('/api/user');

            $response->assertStatus(200)
                ->assertJson([
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ]);
        });

        test('unauthenticated user cannot get profile', function () {
            $response = $this->getJson('/api/user');

            $response->assertStatus(401)
                ->assertJson([
                    'message' => 'Unauthenticated.',
                ]);
        });

        test('user with invalid token cannot get profile', function () {
            $response = $this->withHeaders([
                'Authorization' => 'Bearer invalid-token',
                'Accept' => 'application/json',
            ])->getJson('/api/user');

            $response->assertStatus(401)
                ->assertJson([
                    'message' => 'Unauthenticated.',
                ]);
        });
    });

    describe('Token Refresh API', function () {

        test('authenticated user can refresh their token', function () {
            $user = User::factory()->create();
            $token = $user->createToken('test-token')->plainTextToken;

            $response = $this->withHeaders([
                'Authorization' => 'Bearer '.$token,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->postJson('/api/refresh-token');

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'user' => [
                        'id',
                        'name',
                        'email',
                    ],
                    'token',
                    'message',
                ]);

            // Verify old token was deleted and new one created
            $this->assertDatabaseCount('personal_access_tokens', 1);
        });

        test('unauthenticated user cannot refresh token', function () {
            $response = $this->postJson('/api/refresh-token');

            $response->assertStatus(401)
                ->assertJson([
                    'message' => 'Unauthenticated.',
                ]);
        });
    });

    describe('Token Expiration', function () {

        test('login creates token with 30-minute expiration', function () {
            $user = User::factory()->create([
                'email' => 'expiration@example.com',
                'password' => bcrypt('password123'),
            ]);

            $response = $this->postJson('/api/login', [
                'email' => 'expiration@example.com',
                'password' => 'password123',
            ]);

            $response->assertStatus(200);

            // Verify token expiration
            $tokenRecord = \Laravel\Sanctum\PersonalAccessToken::where('tokenable_id', $user->id)->first();
            $this->assertNotNull($tokenRecord);
            $this->assertNotNull($tokenRecord->expires_at);
            
            // Check that expiration is approximately 30 minutes from now
            $expirationMinutes = now()->diffInMinutes($tokenRecord->expires_at);
            $this->assertGreaterThanOrEqual(29, $expirationMinutes);
            $this->assertLessThanOrEqual(30, $expirationMinutes);
        });

        test('refresh token creates new token with 30-minute expiration', function () {
            $user = User::factory()->create();
            $token = $user->createToken('test-token')->plainTextToken;

            $response = $this->withHeaders([
                'Authorization' => 'Bearer '.$token,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->postJson('/api/refresh-token');

            $response->assertStatus(200);

            // Verify new token has 30-minute expiration
            $tokenRecord = \Laravel\Sanctum\PersonalAccessToken::where('tokenable_id', $user->id)->first();
            $this->assertNotNull($tokenRecord);
            $this->assertNotNull($tokenRecord->expires_at);
            
            $expirationMinutes = now()->diffInMinutes($tokenRecord->expires_at);
            $this->assertGreaterThanOrEqual(29, $expirationMinutes);
            $this->assertLessThanOrEqual(30, $expirationMinutes);
        });

        test('expired token is rejected by API', function () {
            $user = User::factory()->create();
            
            // Create a token that expires in 1 second
            $token = $user->createToken('expired-token', ['*'], now()->addSeconds(1))->plainTextToken;
            
            // Wait for token to expire
            sleep(2);

            $response = $this->withHeaders([
                'Authorization' => 'Bearer '.$token,
                'Accept' => 'application/json',
            ])->getJson('/api/user');

            $response->assertStatus(401)
                ->assertJson([
                    'message' => 'Unauthenticated.',
                ]);
        });

        test('register creates token with 30-minute expiration', function () {
            $userData = [
                'name' => 'Test User',
                'email' => 'register@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ];

            $response = $this->postJson('/api/register', $userData);

            $response->assertStatus(201);

            // Verify token expiration
            $user = User::where('email', 'register@example.com')->first();
            $tokenRecord = \Laravel\Sanctum\PersonalAccessToken::where('tokenable_id', $user->id)->first();
            $this->assertNotNull($tokenRecord);
            $this->assertNotNull($tokenRecord->expires_at);
            
            $expirationMinutes = now()->diffInMinutes($tokenRecord->expires_at);
            $this->assertGreaterThanOrEqual(29, $expirationMinutes);
            $this->assertLessThanOrEqual(30, $expirationMinutes);
        });
    });

    describe('Complete Authentication Flow', function () {

        test('complete login, authenticated request, and logout flow', function () {
            $user = User::factory()->create([
                'email' => 'flow@example.com',
                'password' => bcrypt('password123'),
            ]);

            // 1. Login
            $loginResponse = $this->postJson('/api/login', [
                'email' => 'flow@example.com',
                'password' => 'password123',
            ]);

            $loginResponse->assertStatus(200);
            $token = $loginResponse->json('token');

            // Verify token was created in database
            $this->assertDatabaseHas('personal_access_tokens', [
                'tokenable_type' => User::class,
                'tokenable_id' => $user->id,
            ]);

            // 2. Make authenticated request
            $userResponse = $this->withHeaders([
                'Authorization' => 'Bearer '.$token,
                'Accept' => 'application/json',
            ])->getJson('/api/user');

            $userResponse->assertStatus(200)
                ->assertJson([
                    'id' => $user->id,
                    'email' => 'flow@example.com',
                ]);

            // 3. Logout
            $logoutResponse = $this->withHeaders([
                'Authorization' => 'Bearer '.$token,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Content-Length' => '0',
            ])->postJson('/api/logout', []);

            $logoutResponse->assertStatus(200)
                ->assertJson([
                    'message' => 'Logged out successfully',
                ]);

            // Verify token was deleted from database
            $this->assertDatabaseMissing('personal_access_tokens', [
                'tokenable_type' => User::class,
                'tokenable_id' => $user->id,
            ]);

            // 4. Create a new request instance to avoid any caching
            $this->refreshApplication();

            // Verify token is invalidated by trying to use it
            $invalidResponse = $this->withHeaders([
                'Authorization' => 'Bearer '.$token,
                'Accept' => 'application/json',
            ])->getJson('/api/user');

            $invalidResponse->assertStatus(401)
                ->assertJson([
                    'message' => 'Unauthenticated.',
                ]);
        });
    });
});
