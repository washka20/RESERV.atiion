<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Query\ListUsers;

final readonly class ListUsersQuery
{
    public function __construct(
        public int $page = 1,
        public int $perPage = 20,
    ) {}
}
