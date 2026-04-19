<?php

declare(strict_types=1);

use App\Modules\Identity\Domain\ValueObject\MembershipRole;
use App\Modules\Identity\Domain\ValueObject\UserId;
use App\Modules\Identity\Infrastructure\Persistence\Model\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function memberAuthHeader(UserModel $user): array
{
    return ['Authorization' => 'Bearer '.identityIssueJwt($user)];
}

it('GET /members lists active members — requires team.view', function (): void {
    $owner = identityInsertUser('team-owner@test.com');
    $staff = identityInsertUser('team-staff@test.com');
    $ownerId = new UserId((string) $owner->getAuthIdentifier());
    $staffId = new UserId((string) $staff->getAuthIdentifier());

    $orgId = insertOrganizationForTests('team-org');
    insertMembershipForTests($ownerId, $orgId, MembershipRole::OWNER);
    insertMembershipForTests($staffId, $orgId, MembershipRole::STAFF);
    $slug = 'team-org-'.substr($orgId->toString(), 0, 8);

    $response = $this->withHeaders(memberAuthHeader($owner))
        ->getJson('/api/v1/organizations/'.$slug.'/members');

    $response->assertStatus(200)
        ->assertJson(['success' => true, 'error' => null])
        ->assertJsonStructure([
            'data' => [
                ['membership_id', 'role', 'user' => ['id', 'email', 'first_name', 'last_name']],
            ],
        ]);

    expect($response->json('data'))->toHaveCount(2);
});

it('POST /members/invite — owner successfully invites existing user', function (): void {
    $owner = identityInsertUser('inv-owner@test.com');
    $invitee = identityInsertUser('inv-invitee@test.com');
    $ownerId = new UserId((string) $owner->getAuthIdentifier());
    $orgId = insertOrganizationForTests('invite-org');
    insertMembershipForTests($ownerId, $orgId, MembershipRole::OWNER);
    $slug = 'invite-org-'.substr($orgId->toString(), 0, 8);

    $response = $this->withHeaders(memberAuthHeader($owner))
        ->postJson('/api/v1/organizations/'.$slug.'/members/invite', [
            'email' => 'inv-invitee@test.com',
            'role' => 'staff',
        ]);

    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
            'data' => [
                'user_id' => $invitee->id,
                'organization_id' => $orgId->toString(),
                'role' => 'staff',
            ],
        ]);
});

it('POST /members/invite — 404 if invitee email unknown', function (): void {
    $owner = identityInsertUser('inv-unknown-owner@test.com');
    $ownerId = new UserId((string) $owner->getAuthIdentifier());
    $orgId = insertOrganizationForTests('invite-unknown-org');
    insertMembershipForTests($ownerId, $orgId, MembershipRole::OWNER);
    $slug = 'invite-unknown-org-'.substr($orgId->toString(), 0, 8);

    $response = $this->withHeaders(memberAuthHeader($owner))
        ->postJson('/api/v1/organizations/'.$slug.'/members/invite', [
            'email' => 'nobody@nowhere.local',
            'role' => 'staff',
        ]);

    $response->assertStatus(404)
        ->assertJson(['error' => ['code' => 'USER_NOT_FOUND']]);
});

it('POST /members/invite — 409 when user already member', function (): void {
    $owner = identityInsertUser('dup-owner@test.com');
    $existing = identityInsertUser('dup-existing@test.com');
    $ownerId = new UserId((string) $owner->getAuthIdentifier());
    $existingId = new UserId((string) $existing->getAuthIdentifier());
    $orgId = insertOrganizationForTests('dup-org');
    insertMembershipForTests($ownerId, $orgId, MembershipRole::OWNER);
    insertMembershipForTests($existingId, $orgId, MembershipRole::STAFF);
    $slug = 'dup-org-'.substr($orgId->toString(), 0, 8);

    $response = $this->withHeaders(memberAuthHeader($owner))
        ->postJson('/api/v1/organizations/'.$slug.'/members/invite', [
            'email' => 'dup-existing@test.com',
            'role' => 'viewer',
        ]);

    $response->assertStatus(409)
        ->assertJson(['error' => ['code' => 'MEMBERSHIP_ALREADY_EXISTS']]);
});

it('POST /members/invite — staff role forbidden (requires team.manage)', function (): void {
    $staff = identityInsertUser('staff-inv@test.com');
    $staffId = new UserId((string) $staff->getAuthIdentifier());
    $orgId = insertOrganizationForTests('staff-inv-org');
    insertMembershipForTests($staffId, $orgId, MembershipRole::STAFF);
    $slug = 'staff-inv-org-'.substr($orgId->toString(), 0, 8);

    $response = $this->withHeaders(memberAuthHeader($staff))
        ->postJson('/api/v1/organizations/'.$slug.'/members/invite', [
            'email' => 'x@x.com',
            'role' => 'viewer',
        ]);

    $response->assertStatus(403)
        ->assertJson(['error' => ['code' => 'FORBIDDEN_INSUFFICIENT_ROLE']]);
});

it('DELETE /members/{id} revokes membership', function (): void {
    $owner = identityInsertUser('rev-owner@test.com');
    $target = identityInsertUser('rev-target@test.com');
    $ownerId = new UserId((string) $owner->getAuthIdentifier());
    $targetId = new UserId((string) $target->getAuthIdentifier());
    $orgId = insertOrganizationForTests('rev-org');
    insertMembershipForTests($ownerId, $orgId, MembershipRole::OWNER);
    $targetMembershipId = insertMembershipForTests($targetId, $orgId, MembershipRole::STAFF);
    $slug = 'rev-org-'.substr($orgId->toString(), 0, 8);

    $response = $this->withHeaders(memberAuthHeader($owner))
        ->deleteJson('/api/v1/organizations/'.$slug.'/members/'.$targetMembershipId->toString());

    $response->assertStatus(204);

    $this->assertDatabaseMissing('memberships', [
        'id' => $targetMembershipId->toString(),
    ]);
});

it('DELETE /members/{id} — 409 if revoking last owner', function (): void {
    $owner = identityInsertUser('last-owner@test.com');
    $ownerId = new UserId((string) $owner->getAuthIdentifier());
    $orgId = insertOrganizationForTests('last-owner-org');
    $ownerMembershipId = insertMembershipForTests($ownerId, $orgId, MembershipRole::OWNER);
    $slug = 'last-owner-org-'.substr($orgId->toString(), 0, 8);

    $response = $this->withHeaders(memberAuthHeader($owner))
        ->deleteJson('/api/v1/organizations/'.$slug.'/members/'.$ownerMembershipId->toString());

    $response->assertStatus(409)
        ->assertJson(['error' => ['code' => 'MEMBERSHIP_LAST_OWNER']]);
});

it('PATCH /members/{id}/role — changes role', function (): void {
    $owner = identityInsertUser('role-owner@test.com');
    $target = identityInsertUser('role-target@test.com');
    $ownerId = new UserId((string) $owner->getAuthIdentifier());
    $targetId = new UserId((string) $target->getAuthIdentifier());
    $orgId = insertOrganizationForTests('role-org');
    insertMembershipForTests($ownerId, $orgId, MembershipRole::OWNER);
    $targetMembershipId = insertMembershipForTests($targetId, $orgId, MembershipRole::STAFF);
    $slug = 'role-org-'.substr($orgId->toString(), 0, 8);

    $response = $this->withHeaders(memberAuthHeader($owner))
        ->patchJson('/api/v1/organizations/'.$slug.'/members/'.$targetMembershipId->toString().'/role', [
            'role' => 'admin',
        ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'id' => $targetMembershipId->toString(),
                'role' => 'admin',
            ],
        ]);

    $this->assertDatabaseHas('memberships', [
        'id' => $targetMembershipId->toString(),
        'role' => 'admin',
    ]);
});
