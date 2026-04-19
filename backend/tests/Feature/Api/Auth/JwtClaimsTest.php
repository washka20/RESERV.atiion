<?php

declare(strict_types=1);

use App\Modules\Identity\Application\Command\CreateOrganization\CreateOrganizationCommand;
use App\Modules\Identity\Application\Command\CreateOrganization\CreateOrganizationHandler;
use App\Modules\Identity\Application\Command\InviteMember\InviteMemberCommand;
use App\Modules\Identity\Application\Command\InviteMember\InviteMemberHandler;
use App\Modules\Identity\Application\Command\RevokeMembership\RevokeMembershipCommand;
use App\Modules\Identity\Application\Command\RevokeMembership\RevokeMembershipHandler;
use App\Modules\Identity\Application\Query\ListUserMemberships\ListUserMembershipsHandler;
use App\Modules\Identity\Application\Query\ListUserMemberships\ListUserMembershipsQuery;
use App\Modules\Identity\Application\Service\JwtTokenServiceInterface;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
});

/**
 * @param  array<string, mixed>  $payload
 * @return array<string, mixed>
 */
function registerAndLogin(array $payload): array
{
    /** @var TestCase $testCase */
    $testCase = test();

    $register = $testCase->postJson('/api/v1/auth/register', $payload);
    RateLimiter::clear("login:{$payload['email']}");

    return $register->json('data');
}

function decodeJwtClaims(string $accessToken): array
{
    /** @var JwtTokenServiceInterface $service */
    $service = app(JwtTokenServiceInterface::class);
    $parsed = $service->parseAccess($accessToken);

    return $parsed->claims;
}

it('includes empty memberships array for newly registered user', function (): void {
    $data = registerAndLogin([
        'email' => 'solo@test.com',
        'password' => 'password123',
        'first_name' => 'Solo',
        'last_name' => 'User',
        'middle_name' => null,
    ]);

    $claims = decodeJwtClaims($data['access_token']);

    expect($claims)->toHaveKey('memberships');
    expect($claims['memberships'])->toBe([]);
});

it('includes one owner membership after user creates organization', function (): void {
    $regData = registerAndLogin([
        'email' => 'creator@test.com',
        'password' => 'password123',
        'first_name' => 'Creator',
        'last_name' => 'User',
        'middle_name' => null,
    ]);

    /** @var JwtTokenServiceInterface $jwt */
    $jwt = app(JwtTokenServiceInterface::class);
    $userId = $jwt->parseAccess($regData['access_token'])->userId;

    /** @var CreateOrganizationHandler $createHandler */
    $createHandler = app(CreateOrganizationHandler::class);
    $orgDto = $createHandler->handle(new CreateOrganizationCommand(
        userId: $userId->toString(),
        name: ['ru' => 'Моя Организация'],
        description: [],
        type: 'salon',
        city: 'Москва',
        phone: '+7999111',
        email: 'org@test.com',
    ));

    $login = test()->postJson('/api/v1/auth/login', [
        'email' => 'creator@test.com',
        'password' => 'password123',
    ]);
    $login->assertStatus(200);

    $claims = decodeJwtClaims($login->json('data.access_token'));

    expect($claims['memberships'])->toHaveCount(1);
    expect($claims['memberships'][0]['org_id'])->toBe($orgDto->id);
    expect($claims['memberships'][0]['org_slug'])->toBe($orgDto->slug);
    expect($claims['memberships'][0]['role'])->toBe('owner');
});

it('includes invited staff membership in invitee claims', function (): void {
    $ownerData = registerAndLogin([
        'email' => 'owner@test.com',
        'password' => 'password123',
        'first_name' => 'Owner',
        'last_name' => 'User',
        'middle_name' => null,
    ]);

    registerAndLogin([
        'email' => 'staff@test.com',
        'password' => 'password123',
        'first_name' => 'Staff',
        'last_name' => 'User',
        'middle_name' => null,
    ]);

    /** @var JwtTokenServiceInterface $jwt */
    $jwt = app(JwtTokenServiceInterface::class);
    $ownerId = $jwt->parseAccess($ownerData['access_token'])->userId;

    /** @var CreateOrganizationHandler $createHandler */
    $createHandler = app(CreateOrganizationHandler::class);
    $orgDto = $createHandler->handle(new CreateOrganizationCommand(
        userId: $ownerId->toString(),
        name: ['ru' => 'Инвайт-организация'],
        description: [],
        type: 'salon',
        city: 'Москва',
        phone: '+7999222',
        email: 'invite-org@test.com',
    ));

    /** @var InviteMemberHandler $inviter */
    $inviter = app(InviteMemberHandler::class);
    $inviter->handle(new InviteMemberCommand(
        organizationSlug: $orgDto->slug,
        actorUserId: $ownerId->toString(),
        inviteeEmail: 'staff@test.com',
        role: 'staff',
    ));

    $login = test()->postJson('/api/v1/auth/login', [
        'email' => 'staff@test.com',
        'password' => 'password123',
    ]);
    $login->assertStatus(200);

    $claims = decodeJwtClaims($login->json('data.access_token'));

    expect($claims['memberships'])->toHaveCount(1);
    expect($claims['memberships'][0]['org_slug'])->toBe($orgDto->slug);
    expect($claims['memberships'][0]['role'])->toBe('staff');
});

it('includes multiple memberships when user is member of two orgs', function (): void {
    $userData = registerAndLogin([
        'email' => 'multi@test.com',
        'password' => 'password123',
        'first_name' => 'Multi',
        'last_name' => 'User',
        'middle_name' => null,
    ]);

    /** @var JwtTokenServiceInterface $jwt */
    $jwt = app(JwtTokenServiceInterface::class);
    $userId = $jwt->parseAccess($userData['access_token'])->userId;

    /** @var CreateOrganizationHandler $createHandler */
    $createHandler = app(CreateOrganizationHandler::class);

    $createHandler->handle(new CreateOrganizationCommand(
        userId: $userId->toString(),
        name: ['ru' => 'Первая'],
        description: [],
        type: 'salon',
        city: 'Москва',
        phone: '+7999111',
        email: 'org1@test.com',
    ));

    $createHandler->handle(new CreateOrganizationCommand(
        userId: $userId->toString(),
        name: ['ru' => 'Вторая'],
        description: [],
        type: 'rental',
        city: 'Сочи',
        phone: '+7999222',
        email: 'org2@test.com',
    ));

    $login = test()->postJson('/api/v1/auth/login', [
        'email' => 'multi@test.com',
        'password' => 'password123',
    ]);
    $login->assertStatus(200);

    $claims = decodeJwtClaims($login->json('data.access_token'));

    expect($claims['memberships'])->toHaveCount(2);
});

it('refresh re-fetches fresh memberships after revoke', function (): void {
    $ownerData = registerAndLogin([
        'email' => 'rev-owner@test.com',
        'password' => 'password123',
        'first_name' => 'Owner',
        'last_name' => 'User',
        'middle_name' => null,
    ]);
    $staffData = registerAndLogin([
        'email' => 'rev-staff@test.com',
        'password' => 'password123',
        'first_name' => 'Staff',
        'last_name' => 'User',
        'middle_name' => null,
    ]);

    /** @var JwtTokenServiceInterface $jwt */
    $jwt = app(JwtTokenServiceInterface::class);
    $ownerId = $jwt->parseAccess($ownerData['access_token'])->userId;
    $staffId = $jwt->parseAccess($staffData['access_token'])->userId;

    /** @var CreateOrganizationHandler $createHandler */
    $createHandler = app(CreateOrganizationHandler::class);
    $orgDto = $createHandler->handle(new CreateOrganizationCommand(
        userId: $ownerId->toString(),
        name: ['ru' => 'Revoke Org'],
        description: [],
        type: 'salon',
        city: 'Москва',
        phone: '+7999222',
        email: 'rev-org@test.com',
    ));

    /** @var InviteMemberHandler $inviter */
    $inviter = app(InviteMemberHandler::class);
    $inviter->handle(new InviteMemberCommand(
        organizationSlug: $orgDto->slug,
        actorUserId: $ownerId->toString(),
        inviteeEmail: 'rev-staff@test.com',
        role: 'staff',
    ));

    $loginBefore = test()->postJson('/api/v1/auth/login', [
        'email' => 'rev-staff@test.com',
        'password' => 'password123',
    ]);
    $beforeClaims = decodeJwtClaims($loginBefore->json('data.access_token'));
    expect($beforeClaims['memberships'])->toHaveCount(1);

    /** @var RevokeMembershipHandler $revoker */
    $revoker = app(RevokeMembershipHandler::class);
    $staffMembership = $beforeClaims['memberships'][0];

    $membershipsQuery = app(ListUserMembershipsHandler::class)
        ->handle(new ListUserMembershipsQuery($staffId->toString()));
    $membershipId = $membershipsQuery[0]->membershipId;

    $revoker->handle(new RevokeMembershipCommand(
        organizationSlug: $orgDto->slug,
        actorUserId: $ownerId->toString(),
        targetMembershipId: $membershipId,
    ));

    $refresh = test()->postJson('/api/v1/auth/refresh', [
        'refresh_token' => $loginBefore->json('data.refresh_token'),
    ]);
    $refresh->assertStatus(200);

    $afterClaims = decodeJwtClaims($refresh->json('data.access_token'));
    expect($afterClaims['memberships'])->toBe([]);
});
