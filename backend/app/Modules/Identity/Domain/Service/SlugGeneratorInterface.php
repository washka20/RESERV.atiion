<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\Service;

use App\Modules\Identity\Domain\ValueObject\OrganizationSlug;

/**
 * Генерирует валидный уникальный OrganizationSlug из произвольной строки.
 *
 * Реализация (Infrastructure layer) берёт на себя:
 *  - транслитерацию кириллицы / диакритики → latin-1
 *  - приведение к lower-case [a-z0-9-]
 *  - удаление двойных дефисов и trim
 *  - дополнение до MIN_LENGTH при коротких source'ах
 *  - разрешение collisions через суффикс -2, -3, ... (проверка в OrganizationRepository)
 */
interface SlugGeneratorInterface
{
    /**
     * Возвращает OrganizationSlug, гарантированно уникальный в БД на момент вызова.
     * Реальная защита от race — уникальный индекс на уровне Postgres.
     */
    public function generate(string $source): OrganizationSlug;
}
