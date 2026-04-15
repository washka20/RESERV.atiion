<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Domain\Exception;

use App\Modules\Catalog\Domain\ValueObject\ServiceId;
use App\Shared\Domain\Exception\DomainException;

/**
 * Бросается, когда услуга не найдена по идентификатору.
 */
final class ServiceNotFoundException extends DomainException
{
    public static function byId(ServiceId $id): self
    {
        return new self(sprintf('Service with id "%s" not found', $id->toString()));
    }

    public function errorCode(): string
    {
        return 'CATALOG_SERVICE_NOT_FOUND';
    }
}
