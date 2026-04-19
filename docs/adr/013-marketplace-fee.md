# ADR-013: Marketplace fee 10% (flat) с банкерским округлением

**Status:** Accepted
**Date:** 2026-04-19

## Context

Платформе нужна revenue model. Решили брать комиссию с каждой брони — это самая очевидная монетизация двустороннего marketplace (customer ↔ провайдер). До реализации Payment BC нужно определить:

- Какой процент.
- Flat (единый для всех) или per-category / tiered.
- Как округлять при неделимых центах.
- Хранить ли процент как snapshot или пересчитывать из config.

## Decision

- **10% flat** от gross amount, configurable через `config/payments.php::marketplace_fee_percent` (env `MARKETPLACE_FEE_PERCENT`).
- **Banker's rounding** (`PHP_ROUND_HALF_EVEN`) — round half to even для честного распределения без systematic bias.
- **Snapshot** `marketplace_fee_percent` column в `payments` table — замораживает % на момент транзакции для аудита.
- Provider получает net (90%), видит gross/fee/net breakdown в Payouts UI.

Реализация: `App\Modules\Payment\Domain\ValueObject\MarketplaceFee::calculate(Money $gross, Percentage $percent)`.

## Consequences

**Плюсы:**

- Простая модель, easy to reason about для customer и provider.
- Banker's rounding fair в long-run — нет systematic bias в пользу платформы.
- Snapshot позволяет менять config без ретроактивного эффекта, сохраняет аудит-трейл.
- Flat % легко объяснить в договоре с провайдером.

**Минусы:**

- Flat 10% не оптимален для разных tier-ов услуг — дорогие услуги (например, массаж за 10 000 RUB) могут резонно требовать меньшего %.
- Нет promo codes / discounts на старте — усложняет acquisition-кампании.
- Per-organization кастомные ставки не поддерживаются — нельзя дать VIP-провайдеру 5%.

Эти минусы приняты сознательно для MVP. Эволюция — в Plan N+1 по мере реального feedback.

## Alternatives considered

1. **Per-category fee** (стрижки 15%, массаж 8%) — отвергнуто: нет данных о реальной economics разных ниш. Добавим когда появится >50 провайдеров и можно будет измерить.
2. **Tiered fee** (первые 10 000 RUB — 0%, дальше 10%) — отвергнуто: усложняет модель без доказанной необходимости. YAGNI.
3. **Transaction fee** (flat 50 RUB за бронирование) — отвергнуто: не масштабируется по диапазону цен, делает дешёвые бронирования невыгодными для customer.
4. **Subscription-based** (Pro-tier за 5000 RUB/месяц без комиссии) — отвергнуто для MVP: требует аккаунт-billing, recurring payments, trial handling — самостоятельный subsystem.
5. **Round half up** вместо half even — отвергнуто: systematic bias в пользу платформы на масштабе. Banker's rounding — индустриальный стандарт финансовых расчётов.

## Related

- `backend/app/Modules/Payment/Domain/ValueObject/MarketplaceFee.php`
- `backend/app/Modules/Payment/Domain/ValueObject/Percentage.php`
- [`docs/patterns/marketplace-fee.md`](../patterns/marketplace-fee.md)
- [Payment Module README](../../backend/app/Modules/Payment/README.md)
