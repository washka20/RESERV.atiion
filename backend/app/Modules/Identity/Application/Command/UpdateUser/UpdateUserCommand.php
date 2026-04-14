<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Command\UpdateUser;

final readonly class UpdateUserCommand
{
    public function __construct(
        public string $userId,
        public ?string $email,
        public ?string $firstName,
        public ?string $lastName,
        public ?string $middleName,
    ) {}
}
