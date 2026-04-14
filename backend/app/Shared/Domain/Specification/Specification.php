<?php

declare(strict_types=1);

namespace App\Shared\Domain\Specification;

/**
 * Базовый класс спецификации. Паттерн Specification для компонуемых бизнес-правил.
 *
 * Поддерживает композицию через and(), or(), not().
 * Провальная причина доступна через failureReason() после isSatisfiedBy() === false.
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

    /**
     * Создаёт конъюнкцию: оба условия должны выполняться.
     */
    public function and(Specification $other): Specification
    {
        return new AndSpecification($this, $other);
    }

    /**
     * Создаёт дизъюнкцию: хотя бы одно условие должно выполняться.
     */
    public function or(Specification $other): Specification
    {
        return new OrSpecification($this, $other);
    }

    /**
     * Создаёт инверсию: условие не должно выполняться.
     */
    public function not(): Specification
    {
        return new NotSpecification($this);
    }
}
