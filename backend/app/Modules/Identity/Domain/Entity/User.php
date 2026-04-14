<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\Entity;

use App\Modules\Identity\Domain\Event\UserEmailVerified;
use App\Modules\Identity\Domain\Event\UserRegistered;
use App\Modules\Identity\Domain\Event\UserRoleAssigned;
use App\Modules\Identity\Domain\ValueObject\Email;
use App\Modules\Identity\Domain\ValueObject\FullName;
use App\Modules\Identity\Domain\ValueObject\HashedPassword;
use App\Modules\Identity\Domain\ValueObject\UserId;
use App\Shared\Domain\AggregateRoot;
use DateTimeImmutable;

/**
 * User aggregate root. Identity BC.
 *
 * Поддерживает регистрацию, назначение ролей, верификацию email и смену пароля.
 * Восстановление из БД — через User::restore() без генерации событий.
 */
final class User extends AggregateRoot
{
    /**
     * @param  list<Role>  $roles
     */
    private function __construct(
        private readonly UserId $id,
        private Email $email,
        private HashedPassword $passwordHash,
        private FullName $fullName,
        private array $roles,
        private ?DateTimeImmutable $emailVerifiedAt,
        private readonly DateTimeImmutable $createdAt,
    ) {}

    /**
     * Создаёт нового пользователя и записывает событие UserRegistered.
     */
    public static function register(
        UserId $id,
        Email $email,
        HashedPassword $passwordHash,
        FullName $fullName,
    ): self {
        $now = new DateTimeImmutable();
        $user = new self($id, $email, $passwordHash, $fullName, [], null, $now);
        $user->recordEvent(new UserRegistered($id, $email, $now));

        return $user;
    }

    /**
     * Восстанавливает пользователя из хранилища без генерации domain events.
     *
     * @param  list<Role>  $roles
     */
    public static function restore(
        UserId $id,
        Email $email,
        HashedPassword $passwordHash,
        FullName $fullName,
        array $roles,
        ?DateTimeImmutable $emailVerifiedAt,
        DateTimeImmutable $createdAt,
    ): self {
        return new self($id, $email, $passwordHash, $fullName, $roles, $emailVerifiedAt, $createdAt);
    }

    /**
     * Назначает роль пользователю. Идемпотентно: дубликаты по id игнорируются.
     */
    public function assignRole(Role $role): void
    {
        foreach ($this->roles as $existing) {
            if ($existing->id()->equals($role->id())) {
                return;
            }
        }

        $this->roles[] = $role;
        $this->recordEvent(new UserRoleAssigned(
            $this->id,
            $role->id(),
            $role->name(),
            new DateTimeImmutable(),
        ));
    }

    /**
     * Помечает email как верифицированный. Идемпотентно: повторный вызов не пишет событие.
     */
    public function verifyEmail(): void
    {
        if ($this->emailVerifiedAt !== null) {
            return;
        }

        $now = new DateTimeImmutable();
        $this->emailVerifiedAt = $now;
        $this->recordEvent(new UserEmailVerified($this->id, $now));
    }

    /**
     * Меняет хэш пароля пользователя.
     */
    public function changePassword(HashedPassword $newHash): void
    {
        $this->passwordHash = $newHash;
    }

    public function id(): UserId
    {
        return $this->id;
    }

    public function email(): Email
    {
        return $this->email;
    }

    public function passwordHash(): HashedPassword
    {
        return $this->passwordHash;
    }

    public function fullName(): FullName
    {
        return $this->fullName;
    }

    /**
     * @return list<Role>
     */
    public function roles(): array
    {
        return $this->roles;
    }

    public function isEmailVerified(): bool
    {
        return $this->emailVerifiedAt !== null;
    }

    public function emailVerifiedAt(): ?DateTimeImmutable
    {
        return $this->emailVerifiedAt;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
