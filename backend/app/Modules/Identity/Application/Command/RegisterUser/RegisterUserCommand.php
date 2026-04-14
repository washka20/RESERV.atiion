<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Command\RegisterUser;

final readonly class RegisterUserCommand
{
    public function __construct(
        public string $email,
        public string $plaintextPassword,
        public string $firstName,
        public string $lastName,
        public ?string $middleName = null,
    ) {}
}
