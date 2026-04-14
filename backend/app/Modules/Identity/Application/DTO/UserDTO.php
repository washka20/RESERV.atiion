<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\DTO;

final readonly class UserDTO
{
    /**
     * @param  list<string>  $roles
     */
    public function __construct(
        public string $id,
        public string $email,
        public string $firstName,
        public string $lastName,
        public ?string $middleName,
        public array $roles,
        public ?string $emailVerifiedAt,
        public string $createdAt,
    ) {}
}
