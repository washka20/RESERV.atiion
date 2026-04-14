# Specification Pattern

Бизнес-правила в `app/Modules/*/Domain/Specification/`. Композируемы через `and/or/not`, тестируются изолированно.

## API

```php
abstract class Specification
{
    abstract public function isSatisfiedBy(mixed $candidate): bool;
    public function failureReason(): ?string;
    public function and(Specification $other): Specification;
    public function or(Specification $other): Specification;
    public function not(): Specification;

    protected function recordFailure(string $reason): void;
}
```

## Пример — Booking

```php
$canBook = (new ServiceIsActive($service))
    ->and(new SlotIsAvailable($slot))
    ->and(new WithinBookingWindow($date))
    ->and((new UserIsBanned($user))->not());

if (! $canBook->isSatisfiedBy($context)) {
    throw new BookingNotAllowedException($canBook->failureReason());
}
```

## Failure reason

Каждая конкретная спецификация при отказе вызывает `$this->recordFailure("...")` — текст попадает в `failureReason()` родительской композиции и далее — в API error envelope как `error.details.reason`.

## Тестирование

- Каждая спецификация — свой unit-тест (`tests/Unit/Modules/<M>/Domain/Specification/*Test.php`)
- Композиции **не тестируются отдельно** — покрываются unit-тестами `AndSpecification`/`OrSpecification`/`NotSpecification` в Shared
- TDD: Red → Green → Refactor

## Где применяется

| Модуль | Спецификации |
|--------|--------------|
| Booking | CancellationPolicy, BookingPolicy, AvailabilityRules |
| Catalog | ServiceIsActive, ServiceHasCapacity |
| Identity | PasswordStrength, EmailIsUnique |

## См. также
- [ADR 004](../adr/004-specification-pattern.md)
- [`app/Shared/Domain/Specification/`](../../backend/app/Shared/Domain/Specification/)
