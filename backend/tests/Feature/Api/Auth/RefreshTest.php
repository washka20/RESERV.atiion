<?php

declare(strict_types=1);

use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
});

/**
 * Registers a new user and returns the token pair from response.
 *
 * @return array{access_token: string, refresh_token: string}
 */
function registerAndGetTokens(): array
{
    $response = test()->postJson('/api/v1/auth/register', [
        'email' => 'refresh@test.com',
        'password' => 'password123',
        'first_name' => 'A',
        'last_name' => 'B',
        'middle_name' => null,
    ]);

    return [
        'access_token' => (string) $response->json('data.access_token'),
        'refresh_token' => (string) $response->json('data.refresh_token'),
    ];
}

it('refreshes tokens and returns new pair', function (): void {
    $tokens = registerAndGetTokens();

    $response = $this->postJson('/api/v1/auth/refresh', [
        'refresh_token' => $tokens['refresh_token'],
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

    // новые токены отличаются от старых
    expect($response->json('data.access_token'))->not->toBe($tokens['access_token']);
    expect($response->json('data.refresh_token'))->not->toBe($tokens['refresh_token']);
});

it('rejects reuse of old refresh token after rotation', function (): void {
    $tokens = registerAndGetTokens();

    // первый refresh — успешный (токен ротируется)
    $this->postJson('/api/v1/auth/refresh', [
        'refresh_token' => $tokens['refresh_token'],
    ])->assertStatus(200);

    // повторный refresh со старым токеном — 401
    $response = $this->postJson('/api/v1/auth/refresh', [
        'refresh_token' => $tokens['refresh_token'],
    ]);

    $response->assertStatus(401)
        ->assertJson([
            'success' => false,
            'error' => ['code' => 'INVALID_REFRESH'],
        ]);
});

it('rejects unknown refresh token with 401', function (): void {
    $response = $this->postJson('/api/v1/auth/refresh', [
        'refresh_token' => 'completely-unknown-token-value',
    ]);

    $response->assertStatus(401)
        ->assertJson([
            'success' => false,
            'error' => ['code' => 'INVALID_REFRESH'],
        ]);
});
