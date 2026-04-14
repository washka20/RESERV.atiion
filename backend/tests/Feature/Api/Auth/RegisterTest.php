<?php

declare(strict_types=1);

use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
});

it('registers new user and returns 201 with envelope + tokens', function (): void {
    $response = $this->postJson('/api/v1/auth/register', [
        'email' => 'new@user.com',
        'password' => 'password123',
        'first_name' => 'John',
        'last_name' => 'Doe',
        'middle_name' => null,
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'success',
            'data' => [
                'user' => ['id', 'email', 'first_name', 'last_name', 'middle_name', 'roles', 'email_verified_at', 'created_at'],
                'access_token',
                'refresh_token',
                'expires_in',
                'token_type',
            ],
            'error',
            'meta',
        ])
        ->assertJson([
            'success' => true,
            'error' => null,
            'meta' => null,
            'data' => [
                'token_type' => 'Bearer',
            ],
        ]);

    $this->assertDatabaseHas('users', ['email' => 'new@user.com']);
});

it('rejects duplicate email with 422', function (): void {
    $payload = [
        'email' => 'dup@user.com',
        'password' => 'password123',
        'first_name' => 'A',
        'last_name' => 'B',
        'middle_name' => null,
    ];

    $this->postJson('/api/v1/auth/register', $payload)->assertStatus(201);
    $this->postJson('/api/v1/auth/register', $payload)->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

it('validates required fields', function (array $payload, array $expectedErrors): void {
    $this->postJson('/api/v1/auth/register', $payload)
        ->assertStatus(422)
        ->assertJsonValidationErrors($expectedErrors);
})->with([
    [['password' => 'pw12345678', 'first_name' => 'A', 'last_name' => 'B'], ['email']],
    [['email' => 'a@b.com', 'first_name' => 'A', 'last_name' => 'B'], ['password']],
    [['email' => 'not-email', 'password' => 'pw12345678', 'first_name' => 'A', 'last_name' => 'B'], ['email']],
    [['email' => 'a@b.com', 'password' => 'short', 'first_name' => 'A', 'last_name' => 'B'], ['password']],
]);
