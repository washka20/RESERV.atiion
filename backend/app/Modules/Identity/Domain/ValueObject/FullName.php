<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\ValueObject;

use InvalidArgumentException;

/**
 * User's full name value object. Supports optional middle name.
 */
final readonly class FullName
{
    public function __construct(
        private string $firstName,
        private string $lastName,
        private ?string $middleName = null,
    ) {
        if (trim($firstName) === '') {
            throw new InvalidArgumentException('firstName is required');
        }
        if (trim($lastName) === '') {
            throw new InvalidArgumentException('lastName is required');
        }
    }

    public function firstName(): string
    {
        return $this->firstName;
    }

    public function lastName(): string
    {
        return $this->lastName;
    }

    public function middleName(): ?string
    {
        return $this->middleName;
    }

    /**
     * Returns formatted full name: "First [Middle] Last".
     */
    public function full(): string
    {
        $parts = [$this->firstName];
        if ($this->middleName !== null && trim($this->middleName) !== '') {
            $parts[] = $this->middleName;
        }
        $parts[] = $this->lastName;

        return implode(' ', $parts);
    }
}
