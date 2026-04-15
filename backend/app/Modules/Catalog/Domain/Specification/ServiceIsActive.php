<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Domain\Specification;

use App\Modules\Catalog\Domain\Entity\Service;
use App\Shared\Domain\Specification\Specification;

/**
 * Услуга активна (isActive = true).
 *
 * Используется для фильтрации каталога (показываем только активные услуги).
 */
final class ServiceIsActive extends Specification
{
    public function isSatisfiedBy(mixed $candidate): bool
    {
        if (! $candidate instanceof Service) {
            $this->recordFailure('candidate is not a Service');

            return false;
        }

        if (! $candidate->isActive()) {
            $this->recordFailure('service is deactivated');

            return false;
        }

        return true;
    }
}
