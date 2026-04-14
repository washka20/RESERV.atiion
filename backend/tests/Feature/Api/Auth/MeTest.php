<?php

declare(strict_types=1);

use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
});

/**
 * Registers a user and returns the access token.
 */
function registerAndGetAccessToken(): string
{
    $response = test()->postJson('/api/v1/auth/register', [
        'email' => 'me@test.com',
        'password' => 'password123',
        'first_name' => 'John',
        'last_name' => 'Doe',
        'middle_name' => null,
    ]);

    return (string) $response->json('data.access_token');
}

it('returns 401 NO_TOKEN without Authorization header', function (): void {
    $response = $this->getJson('/api/v1/auth/me');

    $response->assertStatus(401)
        ->assertJson(['success' => false, 'error' => ['code' => 'NO_TOKEN']]);
});

it('returns 401 INVALID_TOKEN with malformed bearer token', function (): void {
    $response = $this->withHeaders(['Authorization' => 'Bearer invalid-token'])
        ->getJson('/api/v1/auth/me');

    $response->assertStatus(401)
        ->assertJson(['success' => false, 'error' => ['code' => 'INVALID_TOKEN']]);
});

it('returns user profile with valid access token', function (): void {
    $token = registerAndGetAccessToken();

    $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
        ->getJson('/api/v1/auth/me');

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'email' => 'me@test.com',
                'first_name' => 'John',
                'last_name' => 'Doe',
            ],
        ]);
});
