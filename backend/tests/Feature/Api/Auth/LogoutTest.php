<?php

declare(strict_types=1);

use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
});

it('logs out successfully and returns 204', function (): void {
    $register = $this->postJson('/api/v1/auth/register', [
        'email' => 'logout@test.com',
        'password' => 'password123',
        'first_name' => 'A',
        'last_name' => 'B',
        'middle_name' => null,
    ]);

    $accessToken = (string) $register->json('data.access_token');
    $refreshToken = (string) $register->json('data.refresh_token');

    $response = $this->withHeaders(['Authorization' => "Bearer {$accessToken}"])
        ->postJson('/api/v1/auth/logout', [
            'refresh_token' => $refreshToken,
        ]);

    $response->assertStatus(204);
});

it('makes refresh token invalid after logout', function (): void {
    $register = $this->postJson('/api/v1/auth/register', [
        'email' => 'logout2@test.com',
        'password' => 'password123',
        'first_name' => 'A',
        'last_name' => 'B',
        'middle_name' => null,
    ]);

    $accessToken = (string) $register->json('data.access_token');
    $refreshToken = (string) $register->json('data.refresh_token');

    // выход с отзывом refresh token
    $this->withHeaders(['Authorization' => "Bearer {$accessToken}"])
        ->postJson('/api/v1/auth/logout', [
            'refresh_token' => $refreshToken,
        ])->assertStatus(204);

    // попытка использовать старый refresh token → 401
    $response = $this->postJson('/api/v1/auth/refresh', [
        'refresh_token' => $refreshToken,
    ]);

    $response->assertStatus(401)
        ->assertJson([
            'success' => false,
            'error' => ['code' => 'INVALID_REFRESH'],
        ]);
});
