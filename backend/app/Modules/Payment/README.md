# Payment Module

> Bounded Context: платежи, marketplace fee, payouts провайдерам.

## Purpose

Платежи (MVP `NullGateway`, реальный Stripe/ЮKassa позже) + marketplace fee 10% + payouts providers.

## Aggregates

- **Payment** — booking payment (`pending` → `paid` / `failed` / `refunded`). Aggregate root, emits domain events.
- **PayoutTransaction** — отдельная транзакция выплаты per booking (`gross` / `fee` / `net`, `pending` → `processing` → `paid` / `failed`).
- **PayoutSettings** — банковские реквизиты per organization (1:1).

## Value Objects

- `PaymentId`, `PaymentStatus`, `PaymentMethod`
- `Percentage`, `MarketplaceFee` (banker's rounding)
- `PayoutTransactionId`, `PayoutStatus`, `PayoutSchedule`, `PayoutSettingsId`
- `BankAccount` — plaintext внутри, шифруется через `Crypt` при сохранении в Eloquent-маппере.

## Published Events (через Outbox `reliable` где помечено)

- `PaymentInitiated` (reliable=false)
- `PaymentReceived` (**reliable=true**)
- `PaymentFailed` (reliable=false)
- `PaymentRefunded` (**reliable=true**)
- `PayoutTransactionCreated` (reliable=false)
- `PayoutMarkedPaid` (**reliable=true**)
- `PayoutSettingsUpdated` (reliable=false, без чувствительных полей в payload)

## Subscribed Events

- `BookingCreated` → `InitiatePaymentOnBookingCreated` → `InitiatePaymentCommand`
- `BookingCancelled` → `RefundPaymentOnBookingCancelled` → `RefundPaymentCommand`
- `PaymentReceived` →
  - `ConfirmBookingOnPaymentReceived` → `ConfirmBookingCommand`
  - `CreatePayoutTransactionOnPaymentReceived` → `CreatePayoutTransactionCommand`

## Gateways

- `NullPaymentGateway` — MVP, always success, пишет в `payments` log channel.
- Future: Stripe / ЮKassa / Tinkoff — реализуют `PaymentGatewayInterface`.

## Marketplace Fee

10% default (configurable в `config/payments.php::marketplace_fee_percent`), banker's rounding (`PHP_ROUND_HALF_EVEN`). Подробнее: [`docs/patterns/marketplace-fee.md`](../../../../docs/patterns/marketplace-fee.md).

## Outbox

Все критичные события Payment/Payout идут через `outbox_messages` table → `OutboxWorker`. См. [`docs/patterns/outbox-pattern.md`](../../../../docs/patterns/outbox-pattern.md).

## API

См. [`docs/api/payouts.md`](../../../../docs/api/payouts.md).

## Filament

- `/admin/payments` — `PaymentResource` (admin/manager). Row actions: Mark Paid, Refund.
- `/admin/payout-transactions` — `PayoutTransactionResource`. Actions: Mark Paid, Process Batch.

## Artisan Commands

- `app:outbox:work [--once]` — outbox worker, graceful SIGTERM.
- `app:payouts:process [--force]` — батч обработка pending payouts по schedule (weekly Tuesday).

## Config

`config/payments.php`:

- `default_gateway` — `null` (MVP)
- `marketplace_fee_percent` — `10`
- `default_currency` — `RUB`
- `payouts.default_minimum_cents` — `100000` (1000 RUB)
- `payouts.default_schedule` — `weekly`
- `outbox.worker_batch_size` — `50`
- `outbox.max_retries` — `10`

## Dependencies

- `App\Modules\Identity\Domain\ValueObject\OrganizationId` — ссылка на организацию
- `App\Modules\Booking\Domain\ValueObject\BookingId` — ссылка на booking
- `App\Modules\Catalog\Domain\ValueObject\Money` — денежные суммы
- `App\Shared\Application\Bus\{CommandBusInterface,QueryBusInterface}` — оркестрация
- `App\Shared\Application\Outbox\OutboxPublisherInterface` — events reliable delivery
- `App\Shared\Application\Identity\MembershipLookupInterface` — owner/member check

## Тесты

- Unit: `backend/tests/Unit/Modules/Payment/`
- Feature: `backend/tests/Feature/Api/Payment/` + `backend/tests/Feature/Filament/Payment/`
- Architecture: `backend/tests/Architecture/` (Domain/Application изоляция)
