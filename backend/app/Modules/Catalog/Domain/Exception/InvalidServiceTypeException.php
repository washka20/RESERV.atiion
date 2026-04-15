<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;

/**
 * Бросается при нарушении инвариантов ServiceType:
 * TIME_SLOT требует duration, QUANTITY требует totalQuantity > 0.
 */
final class InvalidServiceTypeException extends DomainException
{
    public static function missingDuration(): self
    {
        return new self('Service of type TIME_SLOT requires duration');
    }

    public static function missingQuantity(): self
    {
        return new self('Service of type QUANTITY requires totalQuantity > 0');
    }

    public function errorCode(): string
    {
        return 'CATALOG_INVALID_SERVICE_TYPE';
    }
}
