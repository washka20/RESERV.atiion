<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\ValueObject;

enum RoleName: string
{
    case Admin = 'admin';
    case Manager = 'manager';
    case User = 'user';
}
