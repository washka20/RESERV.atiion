<?php

declare(strict_types=1);

use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
});

function registerAndGetTokenForUpdate(string $email = 'update@test.com'): string
{
    $response = test()->postJson('/api/v1/auth/register', [
        'email' => $email,
        'password' => 'password123',
        'first_name' => 'Initial',
        'last_name' => 'Name',
        'middle_name' => null,
    ]);

    return (string) $response->json('data.access_token');
}

it('requires auth', function (): void {
    $response = $this->putJson('/api/v1/auth/me', ['first_name' => 'X']);

    $response->assertStatus(401)
        ->assertJson(['success' => false, 'error' => ['code' => 'NO_TOKEN']]);
});

it('updates first_name partially', function (): void {
    $token = registerAndGetTokenForUpdate();

    $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
        ->putJson('/api/v1/auth/me', ['first_name' => 'Renamed']);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'email' => 'update@test.com',
                'first_name' => 'Renamed',
                'last_name' => 'Name',
            ],
        ]);
});

it('updates email when unique', function (): void {
    $token = registerAndGetTokenForUpdate();

    $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
        ->putJson('/api/v1/auth/me', ['email' => 'new-email@test.com']);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'email' => 'new-email@test.com',
            ],
        ]);
});

it('returns 409 on duplicate email', function (): void {
    registerAndGetTokenForUpdate('other@test.com');
    $token = registerAndGetTokenForUpdate('me@test.com');

    $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
        ->putJson('/api/v1/auth/me', ['email' => 'other@test.com']);

    $response->assertStatus(409)
        ->assertJson(['success' => false, 'error' => ['code' => 'IDENTITY_DUPLICATE_EMAIL']]);
});

it('returns 422 on invalid email', function (): void {
    $token = registerAndGetTokenForUpdate();

    $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
        ->putJson('/api/v1/auth/me', ['email' => 'not-an-email']);

    $response->assertStatus(422);
});

it('allows empty payload (no-op)', function (): void {
    $token = registerAndGetTokenForUpdate();

    $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
        ->putJson('/api/v1/auth/me', []);

    $response->assertStatus(200)
        ->assertJson(['data' => ['email' => 'update@test.com', 'first_name' => 'Initial']]);
});
