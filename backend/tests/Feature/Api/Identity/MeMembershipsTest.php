<?php

declare(strict_types=1);

use App\Modules\Identity\Domain\ValueObject\MembershipRole;
use App\Modules\Identity\Domain\ValueObject\UserId;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns current user memberships (only non-archived orgs)', function (): void {
    $user = identityInsertUser('me-user@test.com');
    $userId = new UserId((string) $user->getAuthIdentifier());

    $orgA = insertOrganizationForTests('org-a');
    $orgB = insertOrganizationForTests('org-b');
    insertMembershipForTests($userId, $orgA, MembershipRole::OWNER);
    insertMembershipForTests($userId, $orgB, MembershipRole::STAFF);

    $response = $this->withHeaders([
        'Authorization' => 'Bearer '.identityIssueJwt($user),
    ])->getJson('/api/v1/me/memberships');

    $response->assertStatus(200)
        ->assertJson(['success' => true, 'error' => null])
        ->assertJsonStructure([
            'success',
            'data' => [
                ['membership_id', 'organization_id', 'organization_slug', 'role'],
            ],
        ]);

    $data = $response->json('data');
    expect($data)->toHaveCount(2);
    $roles = array_column($data, 'role');
    sort($roles);
    expect($roles)->toBe(['owner', 'staff']);
});

it('returns empty list when user has no memberships', function (): void {
    $user = identityInsertUser('no-memberships@test.com');

    $response = $this->withHeaders([
        'Authorization' => 'Bearer '.identityIssueJwt($user, extraClaims: ['memberships' => []]),
    ])->getJson('/api/v1/me/memberships');

    $response->assertStatus(200)
        ->assertJson(['success' => true, 'data' => [], 'error' => null]);
});

it('401 when no JWT', function (): void {
    $response = $this->getJson('/api/v1/me/memberships');

    $response->assertStatus(401)
        ->assertJson(['success' => false, 'error' => ['code' => 'NO_TOKEN']]);
});
