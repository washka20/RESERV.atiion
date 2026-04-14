<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\Entity;

use App\Modules\Identity\Domain\ValueObject\RoleId;
use App\Modules\Identity\Domain\ValueObject\RoleName;
use DateTimeImmutable;

final readonly class Role
{
    public function __construct(
        private RoleId $id,
        private RoleName $name,
        private ?DateTimeImmutable $createdAt = null,
    ) {}

    public function id(): RoleId
    {
        return $this->id;
    }

    public function name(): RoleName
    {
        return $this->name;
    }

    public function createdAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }
}
