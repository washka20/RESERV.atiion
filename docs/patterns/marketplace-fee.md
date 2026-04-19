# Marketplace Fee

> Комиссия платформы с каждой брони: 10% gross → платформа, 90% net → провайдер.

## What

При оплате бронирования:

- **Gross** — сумма, которую платит customer.
- **Fee** — 10% от gross, остаётся платформе.
- **Net** — gross − fee, выплачивается провайдеру в payout.

Конфигурируется через `config/payments.php::marketplace_fee_percent` (default `10`).

## Formula

```
fee = round(gross * percent / 100, 0, PHP_ROUND_HALF_EVEN)
net = gross - fee
```

Все суммы — целые **cents** (`Money::amountInCents`), округление до целого цента.

Реализация: `App\Modules\Payment\Domain\ValueObject\MarketplaceFee::calculate(Money $gross, Percentage $percent): self`.

## Banker's Rounding (HALF_EVEN)

Используется `PHP_ROUND_HALF_EVEN` — round-half-to-even, aka **banker's rounding**.

**Правило:** при значении ровно на границе (0.5) — округление к ближайшему **чётному** целому.

| value | round half up | round half even |
|-------|---------------|-----------------|
| 0.5   | 1             | **0**           |
| 1.5   | 2             | **2**           |
| 2.5   | 3             | **2**           |
| 3.5   | 4             | **4**           |

**Почему HALF_EVEN:** стандартный HALF_UP даёт systematic bias вверх. На масштабе миллионов транзакций платформа систематически забирала бы чуть больше, чем математически причитается. Banker's rounding в long-run даёт ожидание = 0 bias.

В финансовых стандартах (IEEE 754, Java `BigDecimal.ROUND_HALF_EVEN`, Python `decimal.ROUND_HALF_EVEN`) — это канонический выбор.

## Примеры

| Gross (cents) | Fee 10% raw | Fee rounded (HALF_EVEN) | Net |
|---------------|-------------|-------------------------|-----|
| 240000 (2400 RUB) | 24000.0 | 24000 | 216000 |
| 99900 (999 RUB)   | 9990.0  | 9990  | 89910 |
| 999               | 99.9    | 100   | 899 |
| 5                 | 0.5     | **0** (→ even) | 5 |
| 15                | 1.5     | **2** (→ even) | 13 |
| 25                | 2.5     | **2** (→ even) | 23 |
| 35                | 3.5     | **4** (→ even) | 31 |

Обрати внимание на строки с `.5` — они распределяются честно: вверх / вниз / вверх / вниз.

## Edge cases

- **0 gross** — `MarketplaceFee::calculate()` бросает `InvalidPaymentAmountException`. Создание бесплатной брони не должно идти через Payment pipeline.
- **100% fee** — допустим (net = 0). Admin-only сценарий: платформа забирает всё, провайдер не получает payout. Не рассчитано для штатного flow.
- **Negative gross** — `Money` конструктор бросает `InvalidArgumentException`. Refund — отдельный домен flow, не через `MarketplaceFee::calculate`.
- **Percent > 100** — `Percentage` конструктор бросает. Защита от config-ошибок.

## Snapshot в `payments.marketplace_fee_percent`

Колонка `payments.marketplace_fee_percent` хранит **снапшот** % на момент создания платежа.

**Почему:** изменение `config/payments.php::marketplace_fee_percent` **не** должно ретроактивно пересчитывать fee по старым платежам:

- Аудит-трейл: можно доказать регулятору, какой именно процент применялся к конкретной транзакции.
- Отчёты за период: не меняются задним числом.
- Споры с провайдерами: «мы договаривались о 10%, вы взяли 15%» — в payments строка показывает, что на тот момент было 10%.

**Следствие:** при смене `marketplace_fee_percent` в config новые платежи берут новое значение, старые — сохраняют своё. Это **правильное** поведение. Миграция процента для существующих платежей требует осознанного пересчёта (backfill migration).

## Future

- **Per-category fee** — стрижки 15%, массаж 8%. Добавим когда появится feedback от провайдеров разных ниш.
- **Tiered fee** — первые 10 000 RUB в месяц — 0%, далее 10%. Для привлечения мелких провайдеров.
- **Promo codes / discounts** — временное понижение fee для определённых acquisition-кампаний.
- **Per-organization overrides** — персональные договорённости с VIP-провайдерами (enterprise deals).

Все эти эволюции сохранят snapshot-паттерн: фактический % на момент транзакции всегда зафиксирован в payment row.

## Связанные документы

- [ADR-013: Marketplace fee 10% flat с банкерским округлением](../adr/013-marketplace-fee.md)
- [Payment Module README](../../backend/app/Modules/Payment/README.md)
- `App\Modules\Payment\Domain\ValueObject\MarketplaceFee`
- `App\Modules\Payment\Domain\ValueObject\Percentage`
