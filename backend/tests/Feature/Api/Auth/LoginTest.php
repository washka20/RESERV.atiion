<?php

declare(strict_types=1);

use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);

    $this->postJson('/api/v1/auth/register', [
        'email' => 'login@test.com',
        'password' => 'password123',
        'first_name' => 'A',
        'last_name' => 'B',
        'middle_name' => null,
    ]);

    RateLimiter::clear('login:login@test.com');
});

it('logs in with correct credentials and returns tokens', function (): void {
    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'login@test.com',
        'password' => 'password123',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'data' => ['access_token', 'refresh_token', 'expires_in', 'token_type'],
        ])
        ->assertJson([
            'success' => true,
            'data' => ['token_type' => 'Bearer'],
        ]);
});

it('returns 401 with INVALID_CREDENTIALS for wrong password', function (): void {
    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'login@test.com',
        'password' => 'wrong-password',
    ]);

    $response->assertStatus(401)
        ->assertJson([
            'success' => false,
            'error' => ['code' => 'INVALID_CREDENTIALS'],
        ]);
});

it('returns 401 for unknown email', function (): void {
    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'unknown@test.com',
        'password' => 'password123',
    ]);

    $response->assertStatus(401)
        ->assertJson(['success' => false, 'error' => ['code' => 'INVALID_CREDENTIALS']]);
});

it('throttles login after 5 attempts per minute', function (): void {
    for ($i = 0; $i < 5; $i++) {
        $this->postJson('/api/v1/auth/login', ['email' => 'login@test.com', 'password' => 'wrong']);
    }

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'login@test.com',
        'password' => 'password123',
    ]);

    $response->assertStatus(429);
});
