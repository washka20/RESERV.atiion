<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Query\ListUserMemberships;

/**
 * Query списка memberships пользователя (во всех организациях).
 */
final readonly class ListUserMembershipsQuery
{
    public function __construct(
        public string $userId,
    ) {}
}
