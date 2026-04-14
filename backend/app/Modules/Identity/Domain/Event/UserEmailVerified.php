<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\Event;

use App\Modules\Identity\Domain\ValueObject\UserId;
use App\Shared\Domain\DomainEvent;
use DateTimeImmutable;

final readonly class UserEmailVerified implements DomainEvent
{
    public function __construct(
        private UserId $userId,
        private DateTimeImmutable $occurredAt,
    ) {}

    public function userId(): UserId
    {
        return $this->userId;
    }

    public function aggregateId(): string
    {
        return $this->userId->toString();
    }

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function eventName(): string
    {
        return 'identity.user.email_verified';
    }

    public function payload(): array
    {
        return [
            'user_id' => $this->userId->toString(),
            'occurred_at' => $this->occurredAt->format(DATE_ATOM),
        ];
    }
}
