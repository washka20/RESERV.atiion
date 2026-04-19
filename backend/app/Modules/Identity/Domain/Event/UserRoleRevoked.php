<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\Event;

use App\Modules\Identity\Domain\ValueObject\RoleId;
use App\Modules\Identity\Domain\ValueObject\RoleName;
use App\Modules\Identity\Domain\ValueObject\UserId;
use App\Shared\Domain\DomainEvent;
use DateTimeImmutable;

final readonly class UserRoleRevoked implements DomainEvent
{
    public function __construct(
        private UserId $userId,
        private RoleId $roleId,
        private RoleName $roleName,
        private DateTimeImmutable $occurredAt,
    ) {}

    public function userId(): UserId
    {
        return $this->userId;
    }

    public function roleId(): RoleId
    {
        return $this->roleId;
    }

    public function roleName(): RoleName
    {
        return $this->roleName;
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
        return 'identity.user.role_revoked';
    }

    public function payload(): array
    {
        return [
            'user_id' => $this->userId->toString(),
            'role_id' => $this->roleId->toString(),
            'role_name' => $this->roleName->value,
            'occurred_at' => $this->occurredAt->format(DATE_ATOM),
        ];
    }

    public static function fromPayload(array $payload): self
    {
        return new self(
            new UserId((string) $payload['user_id']),
            new RoleId((string) $payload['role_id']),
            RoleName::from((string) $payload['role_name']),
            new DateTimeImmutable((string) $payload['occurred_at']),
        );
    }
}
