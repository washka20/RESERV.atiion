<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\Repository;

use App\Modules\Identity\Domain\Entity\Organization;
use App\Modules\Identity\Domain\ValueObject\OrganizationId;
use App\Modules\Identity\Domain\ValueObject\OrganizationSlug;
use App\Modules\Identity\Domain\ValueObject\UserId;

/**
 * Репозиторий Organization aggregate.
 *
 * Infrastructure layer предоставляет Eloquent-реализацию; Application handlers
 * зависят только от этого интерфейса. Persistence domain events обрабатывается
 * отдельно (Outbox/EventBus), репозиторий только хранит состояние.
 */
interface OrganizationRepositoryInterface
{
    /**
     * Сохраняет organization (insert или update по id).
     */
    public function save(Organization $organization): void;

    /**
     * Ищет organization по уникальному id.
     */
    public function findById(OrganizationId $id): ?Organization;

    /**
     * Ищет organization по уникальному slug (используется публичным маршрутом /o/{slug}).
     */
    public function findBySlug(OrganizationSlug $slug): ?Organization;

    /**
     * Проверяет существует ли organization с указанным slug — для pre-check'ов
     * в SlugGenerator и валидации формы.
     */
    public function existsBySlug(OrganizationSlug $slug): bool;

    /**
     * Возвращает все organizations, в которых указанный user состоит членом
     * (любая роль, независимо от accepted_at).
     *
     * @return list<Organization>
     */
    public function findByUserId(UserId $userId): array;
}
