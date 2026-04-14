<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Service;

use App\Modules\Identity\Domain\ValueObject\UserId;

final readonly class ParsedClaims
{
    /**
     * @param  array<string, mixed>  $claims
     */
    public function __construct(
        public UserId $userId,
        public array $claims,
    ) {}
}
