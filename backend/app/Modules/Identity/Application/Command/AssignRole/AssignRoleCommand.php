<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Command\AssignRole;

use App\Modules\Identity\Domain\ValueObject\RoleName;

final readonly class AssignRoleCommand
{
    public function __construct(
        public string $userId,
        public RoleName $roleName,
    ) {}
}
