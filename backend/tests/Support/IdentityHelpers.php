<?php

declare(strict_types=1);

use App\Modules\Identity\Application\Service\JwtTokenServiceInterface;
use App\Modules\Identity\Application\Service\UserMembershipsLookupInterface;
use App\Modules\Identity\Domain\ValueObject\MembershipId;
use App\Modules\Identity\Domain\ValueObject\MembershipRole;
use App\Modules\Identity\Domain\ValueObject\OrganizationId;
use App\Modules\Identity\Domain\ValueObject\UserId;
use App\Modules\Identity\Infrastructure\Persistence\Model\MembershipModel;
use App\Modules\Identity\Infrastructure\Persistence\Model\OrganizationModel;
use App\Modules\Identity\Infrastructure\Persistence\Model\UserModel;

/*
 * Identity test helpers — глобальные функции для Feature-тестов.
 *
 * Подключается через composer.json autoload-dev "files" — гарантирует
 * загрузку функций в каждый процесс paratest.
 */

if (! function_exists('insertOrganizationForTests')) {
    /**
     * Raw INSERT organization в БД для Feature/Catalog/Booking тестов, где
     * Service требует organization_id после Plan 12. Минимальный профиль.
     */
    function insertOrganizationForTests(string $slugPrefix = 'test-org'): OrganizationId
    {
        $id = OrganizationId::generate();
        $slug = $slugPrefix.'-'.substr($id->toString(), 0, 8);

        OrganizationModel::query()->insert([
            'id' => $id->toString(),
            'slug' => $slug,
            'name' => json_encode(['ru' => 'Test Org ('.$slug.')'], JSON_UNESCAPED_UNICODE),
            'description' => json_encode([], JSON_UNESCAPED_UNICODE),
            'type' => 'salon',
            'logo_url' => null,
            'city' => 'Moscow',
            'district' => null,
            'phone' => '+7 000 000 00 00',
            'email' => 'test@example.com',
            'verified' => false,
            'cancellation_policy' => 'flexible',
            'rating' => 0,
            'reviews_count' => 0,
            'archived_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $id;
    }
}

if (! function_exists('insertMembershipForTests')) {
    /**
     * Raw INSERT membership связи user ↔ organization с заданной ролью.
     */
    function insertMembershipForTests(
        UserId $userId,
        OrganizationId $organizationId,
        MembershipRole $role = MembershipRole::OWNER,
    ): MembershipId {
        $id = MembershipId::generate();
        MembershipModel::query()->insert([
            'id' => $id->toString(),
            'user_id' => $userId->toString(),
            'organization_id' => $organizationId->toString(),
            'role' => $role->value,
            'invited_by' => null,
            'accepted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $id;
    }
}

if (! function_exists('identityInsertUser')) {
    /**
     * Создаёт пользователя через factory и возвращает UserModel.
     */
    function identityInsertUser(string $email = 'test-user@example.com'): UserModel
    {
        /** @var UserModel $user */
        $user = UserModel::factory()->create(['email' => $email]);

        return $user;
    }
}

if (! function_exists('identityIssueJwt')) {
    /**
     * Выпускает JWT access token для пользователя с memberships claims.
     *
     * Если $extraClaims пустой — берёт memberships пользователя из БД через
     * UserMembershipsLookupInterface (совпадает с behavior AuthService при login).
     *
     * @param  array<string, mixed>|null  $extraClaims
     */
    function identityIssueJwt(UserModel $user, ?array $extraClaims = null): string
    {
        $jwt = app(JwtTokenServiceInterface::class);

        if ($extraClaims === null) {
            $lookup = app(UserMembershipsLookupInterface::class);
            $memberships = $lookup->forUser((string) $user->getAuthIdentifier());
            $extraClaims = [
                'memberships' => array_map(
                    static fn ($m) => [
                        'org_id' => $m->organizationId,
                        'org_slug' => $m->organizationSlug,
                        'role' => $m->role,
                    ],
                    $memberships,
                ),
            ];
        }

        $pair = $jwt->issue(new UserId((string) $user->getAuthIdentifier()), $extraClaims);

        return $pair->accessToken;
    }
}
