<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Command\VerifyEmail;

final readonly class VerifyEmailCommand
{
    public function __construct(public string $userId) {}
}
