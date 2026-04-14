<?php

declare(strict_types=1);

use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
});

it('register response has success/data/error/meta envelope', function (): void {
    $response = $this->postJson('/api/v1/auth/register', [
        'email' => 'envelope-register@test.com',
        'password' => 'password123',
        'first_name' => 'A',
        'last_name' => 'B',
        'middle_name' => null,
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure(['success', 'data', 'error', 'meta'])
        ->assertJson(['success' => true, 'error' => null, 'meta' => null]);
});

it('login response has success/data/error/meta envelope', function (): void {
    $this->postJson('/api/v1/auth/register', [
        'email' => 'envelope-login@test.com',
        'password' => 'password123',
        'first_name' => 'A',
        'last_name' => 'B',
        'middle_name' => null,
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'envelope-login@test.com',
        'password' => 'password123',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure(['success', 'data', 'error', 'meta'])
        ->assertJson(['success' => true, 'error' => null, 'meta' => null]);
});

it('refresh response has success/data/error/meta envelope', function (): void {
    $register = $this->postJson('/api/v1/auth/register', [
        'email' => 'envelope-refresh@test.com',
        'password' => 'password123',
        'first_name' => 'A',
        'last_name' => 'B',
        'middle_name' => null,
    ]);

    $response = $this->postJson('/api/v1/auth/refresh', [
        'refresh_token' => (string) $register->json('data.refresh_token'),
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure(['success', 'data', 'error', 'meta'])
        ->assertJson(['success' => true, 'error' => null, 'meta' => null]);
});

it('me response has success/data/error/meta envelope', function (): void {
    $register = $this->postJson('/api/v1/auth/register', [
        'email' => 'envelope-me@test.com',
        'password' => 'password123',
        'first_name' => 'A',
        'last_name' => 'B',
        'middle_name' => null,
    ]);

    $token = (string) $register->json('data.access_token');

    $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
        ->getJson('/api/v1/auth/me');

    $response->assertStatus(200)
        ->assertJsonStructure(['success', 'data', 'error', 'meta'])
        ->assertJson(['success' => true, 'error' => null, 'meta' => null]);
});

it('error responses have success/data/error/meta envelope', function (string $code, string $message, int $status): void {
    $response = match ($code) {
        'INVALID_CREDENTIALS' => $this->postJson('/api/v1/auth/login', [
            'email' => 'noone@test.com',
            'password' => 'wrong',
        ]),
        'INVALID_REFRESH' => $this->postJson('/api/v1/auth/refresh', [
            'refresh_token' => 'bad-token',
        ]),
        'NO_TOKEN' => $this->getJson('/api/v1/auth/me'),
        default => $this->getJson('/api/v1/auth/me'),
    };

    $response->assertStatus($status)
        ->assertJsonStructure(['success', 'data', 'error' => ['code', 'message'], 'meta'])
        ->assertJson(['success' => false, 'data' => null, 'meta' => null]);
})->with([
    ['INVALID_CREDENTIALS', 'login with unknown email', 401],
    ['INVALID_REFRESH', 'refresh with bad token', 401],
    ['NO_TOKEN', 'me without token', 401],
]);
