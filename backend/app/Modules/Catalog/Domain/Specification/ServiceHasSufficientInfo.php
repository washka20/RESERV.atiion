<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Domain\Specification;

use App\Modules\Catalog\Domain\Entity\Service;
use App\Shared\Domain\Specification\Specification;

/**
 * Услуга имеет достаточную информацию для публикации:
 * непустое name, цена > 0, description длиной >= 10 символов.
 */
final class ServiceHasSufficientInfo extends Specification
{
    private const int MIN_DESCRIPTION_LENGTH = 10;

    public function isSatisfiedBy(mixed $candidate): bool
    {
        if (! $candidate instanceof Service) {
            $this->recordFailure('candidate is not a Service');

            return false;
        }

        if ($candidate->name() === '') {
            $this->recordFailure('name is empty');

            return false;
        }

        if ($candidate->price()->amount() <= 0) {
            $this->recordFailure('price must be greater than 0');

            return false;
        }

        if (mb_strlen($candidate->description()) < self::MIN_DESCRIPTION_LENGTH) {
            $this->recordFailure(sprintf('description must be at least %d characters', self::MIN_DESCRIPTION_LENGTH));

            return false;
        }

        return true;
    }
}
