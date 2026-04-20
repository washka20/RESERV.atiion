<?php

declare(strict_types=1);

namespace App\Shared\Domain\Specification;

/**
 * Базовый класс спецификации. Инкапсулирует одно бизнес-правило с причиной провала.
 *
 * Композиция через and()/or()/not() удалена: реального использования не было
 * (см. ADR-016 — pragmatic DDD scope). Если композиция понадобится, верни их назад.
 */
abstract class Specification
{
    private ?string $failureReason = null;

    /**
     * Проверяет, удовлетворяет ли кандидат спецификации.
     */
    abstract public function isSatisfiedBy(mixed $candidate): bool;

    /**
     * Возвращает причину несоответствия или null, если спецификация удовлетворена.
     */
    public function failureReason(): ?string
    {
        return $this->failureReason;
    }

    /**
     * Записывает причину несоответствия. Вызывается из isSatisfiedBy() при провале.
     */
    protected function recordFailure(string $reason): void
    {
        $this->failureReason = $reason;
    }
}
