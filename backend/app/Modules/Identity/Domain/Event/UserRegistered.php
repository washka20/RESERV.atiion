<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\Event;

use App\Modules\Identity\Domain\ValueObject\Email;
use App\Modules\Identity\Domain\ValueObject\UserId;
use App\Shared\Domain\DomainEvent;
use DateTimeImmutable;

final readonly class UserRegistered implements DomainEvent
{
    public function __construct(
        private UserId $userId,
        private Email $email,
        private DateTimeImmutable $occurredAt,
    ) {}

    public function userId(): UserId
    {
        return $this->userId;
    }

    public function email(): Email
    {
        return $this->email;
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
        return 'identity.user.registered';
    }

    public function payload(): array
    {
        return [
            'user_id' => $this->userId->toString(),
            'email' => $this->email->value(),
            'occurred_at' => $this->occurredAt->format(DATE_ATOM),
        ];
    }

    public static function fromPayload(array $payload): self
    {
        return new self(
            new UserId((string) $payload['user_id']),
            new Email((string) $payload['email']),
            new DateTimeImmutable((string) $payload['occurred_at']),
        );
    }
}
