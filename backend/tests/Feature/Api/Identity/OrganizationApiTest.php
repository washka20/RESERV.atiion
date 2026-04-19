<?php

declare(strict_types=1);

use App\Modules\Identity\Domain\ValueObject\MembershipRole;
use App\Modules\Identity\Domain\ValueObject\UserId;
use App\Modules\Identity\Infrastructure\Persistence\Model\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function orgAuthHeader(UserModel $user): array
{
    return ['Authorization' => 'Bearer '.identityIssueJwt($user)];
}

it('GET /organizations/{slug} returns public profile', function (): void {
    $user = identityInsertUser('view-org@test.com');
    $orgId = insertOrganizationForTests('public-org');

    $response = $this->withHeaders(orgAuthHeader($user))
        ->getJson('/api/v1/organizations/public-org-'.substr($orgId->toString(), 0, 8));

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'error' => null,
            'data' => [
                'id' => $orgId->toString(),
                'type' => 'salon',
                'city' => 'Moscow',
            ],
        ]);
});

it('GET /organizations/{slug} returns 404 for unknown slug', function (): void {
    $user = identityInsertUser('missing-org@test.com');

    $response = $this->withHeaders(orgAuthHeader($user))
        ->getJson('/api/v1/organizations/nonexistent-org');

    $response->assertStatus(404)
        ->assertJson(['success' => false, 'error' => ['code' => 'ORG_NOT_FOUND']]);
});

it('POST /organizations creates org and grants actor OWNER', function (): void {
    $user = identityInsertUser('creator@test.com');

    $response = $this->withHeaders(orgAuthHeader($user))
        ->postJson('/api/v1/organizations', [
            'name' => ['ru' => 'Мой Салон', 'en' => 'My Salon'],
            'description' => ['ru' => 'Описание'],
            'type' => 'salon',
            'city' => 'Moscow',
            'phone' => '+7 999 111 22 33',
            'email' => 'salon@example.com',
        ]);

    $response->assertStatus(201)
        ->assertJson(['success' => true, 'error' => null])
        ->assertJsonStructure([
            'success',
            'data' => ['id', 'slug', 'name', 'type', 'city', 'phone', 'email', 'verified'],
        ]);

    $this->assertDatabaseHas('organizations', [
        'slug' => $response->json('data.slug'),
        'city' => 'Moscow',
    ]);

    $this->assertDatabaseHas('memberships', [
        'user_id' => $user->id,
        'role' => 'owner',
    ]);
});

it('POST /organizations validates required fields', function (): void {
    $user = identityInsertUser('invalid-create@test.com');

    $response = $this->withHeaders(orgAuthHeader($user))
        ->postJson('/api/v1/organizations', [
            'name' => ['ru' => 'x'],
            'type' => 'unknown_type',
        ]);

    $response->assertStatus(422);
});

it('PATCH /organizations/{slug} requires org.member with settings.manage', function (): void {
    $user = identityInsertUser('not-member@test.com');
    $orgId = insertOrganizationForTests('locked-org');
    $slug = 'locked-org-'.substr($orgId->toString(), 0, 8);

    $response = $this->withHeaders(orgAuthHeader($user))
        ->patchJson('/api/v1/organizations/'.$slug, [
            'city' => 'SPb',
        ]);

    $response->assertStatus(403)
        ->assertJson(['error' => ['code' => 'FORBIDDEN_NOT_MEMBER']]);
});

it('PATCH /organizations/{slug} forbidden for staff role', function (): void {
    $user = identityInsertUser('staff@test.com');
    $userId = new UserId((string) $user->getAuthIdentifier());
    $orgId = insertOrganizationForTests('staff-org');
    insertMembershipForTests($userId, $orgId, MembershipRole::STAFF);
    $slug = 'staff-org-'.substr($orgId->toString(), 0, 8);

    $response = $this->withHeaders(orgAuthHeader($user))
        ->patchJson('/api/v1/organizations/'.$slug, [
            'city' => 'SPb',
        ]);

    $response->assertStatus(403)
        ->assertJson(['error' => ['code' => 'FORBIDDEN_INSUFFICIENT_ROLE']]);
});

it('PATCH /organizations/{slug} allowed for owner and updates profile', function (): void {
    $user = identityInsertUser('owner-update@test.com');
    $userId = new UserId((string) $user->getAuthIdentifier());
    $orgId = insertOrganizationForTests('owner-org');
    insertMembershipForTests($userId, $orgId, MembershipRole::OWNER);
    $slug = 'owner-org-'.substr($orgId->toString(), 0, 8);

    $response = $this->withHeaders(orgAuthHeader($user))
        ->patchJson('/api/v1/organizations/'.$slug, [
            'city' => 'Saint Petersburg',
            'phone' => '+7 911 222 33 44',
        ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'city' => 'Saint Petersburg',
                'phone' => '+7 911 222 33 44',
            ],
        ]);
});

it('DELETE /organizations/{slug} forbidden for admin (no organization.archive permission)', function (): void {
    $user = identityInsertUser('admin-no-archive@test.com');
    $userId = new UserId((string) $user->getAuthIdentifier());
    $orgId = insertOrganizationForTests('admin-archive-org');
    insertMembershipForTests($userId, $orgId, MembershipRole::ADMIN);
    $slug = 'admin-archive-org-'.substr($orgId->toString(), 0, 8);

    $response = $this->withHeaders(orgAuthHeader($user))
        ->deleteJson('/api/v1/organizations/'.$slug);

    $response->assertStatus(403)
        ->assertJson(['error' => ['code' => 'FORBIDDEN_INSUFFICIENT_ROLE']]);
});

it('DELETE /organizations/{slug} allowed for owner and archives org', function (): void {
    $user = identityInsertUser('owner-archive@test.com');
    $userId = new UserId((string) $user->getAuthIdentifier());
    $orgId = insertOrganizationForTests('owner-archive-org');
    insertMembershipForTests($userId, $orgId, MembershipRole::OWNER);
    $slug = 'owner-archive-org-'.substr($orgId->toString(), 0, 8);

    $response = $this->withHeaders(orgAuthHeader($user))
        ->deleteJson('/api/v1/organizations/'.$slug);

    $response->assertStatus(204);

    $this->assertDatabaseMissing('organizations', [
        'id' => $orgId->toString(),
        'archived_at' => null,
    ]);
});
