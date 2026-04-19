# Payouts API

> Customer API для owner / member организации: просмотр выплат, управление банковскими реквизитами, статистика.

## Общее

- **Base URL:** `/api/v1/organizations/{slug}`
- **Auth:** JWT (см. [`authentication.md`](authentication.md))
- **Membership:** пользователь должен быть owner / member организации с нужным permission.
- **Envelope:** стандартный `{ success, data, error, meta }` (см. [`README.md`](README.md)).

### Permissions matrix

| Endpoint | Required permission | Роли с доступом |
|----------|--------------------|-----------------|
| `GET /payouts` | `payouts.view` | owner, manager, provider |
| `GET /payout-settings` | `payouts.view` | owner, manager, provider |
| `PUT /payout-settings` | `payouts.manage` | owner only |
| `GET /stats` | `analytics.view` | owner, manager |

Permissions определены в `App\Modules\Identity\Domain\ValueObject\MembershipRole::PERMISSIONS`.

### Общие ошибки

| HTTP | code | Причина |
|------|------|---------|
| 401 | `UNAUTHENTICATED` | Нет/невалидный JWT |
| 403 | `FORBIDDEN_INSUFFICIENT_ROLE` | Нет нужного permission |
| 404 | `ORGANIZATION_NOT_FOUND` | Организация по slug не существует |
| 422 | `VALIDATION_ERROR` | Ошибка валидации payload |

---

## `GET /api/v1/organizations/{slug}/payouts`

Список payout-транзакций организации с пагинацией.

### Query params

- `page` — номер страницы (default `1`, min `1`)
- `per_page` — размер страницы (default `20`, min `1`, max `100`)

### Request

```http
GET /api/v1/organizations/acme-salon/payouts?page=1&per_page=20
Authorization: Bearer <jwt>
```

### Response 200

```json
{
  "success": true,
  "data": [
    {
      "id": "01J8X...",
      "booking_id": "01J8W...",
      "gross_cents": 240000,
      "platform_fee_cents": 24000,
      "net_cents": 216000,
      "currency": "RUB",
      "status": "pending",
      "scheduled_at": "2026-04-22T00:00:00Z",
      "paid_at": null,
      "created_at": "2026-04-19T14:32:10Z"
    }
  ],
  "error": null,
  "meta": {
    "page": 1,
    "per_page": 20,
    "total": 47,
    "last_page": 3
  }
}
```

### Errors

| HTTP | code | Причина |
|------|------|---------|
| 403  | `FORBIDDEN_INSUFFICIENT_ROLE` | Нет `payouts.view` |
| 404  | `ORGANIZATION_NOT_FOUND` | Slug не существует |

---

## `GET /api/v1/organizations/{slug}/payout-settings`

Текущие банковские реквизиты организации. **Номер счёта возвращается только маскированным** (последние 4 цифры). Plaintext не покидает сервер через API.

### Request

```http
GET /api/v1/organizations/acme-salon/payout-settings
Authorization: Bearer <jwt>
```

### Response 200

```json
{
  "success": true,
  "data": {
    "bank_name": "Сбербанк",
    "account_number_masked": "************6789",
    "account_holder": "ООО АКМЕ",
    "bic": "044525225",
    "payout_schedule": "weekly",
    "minimum_payout_cents": 100000
  },
  "error": null,
  "meta": null
}
```

### Errors

| HTTP | code | Причина |
|------|------|---------|
| 403  | `FORBIDDEN_INSUFFICIENT_ROLE` | Нет `payouts.view` |
| 404  | `ORGANIZATION_NOT_FOUND` | Slug не существует |
| 404  | `PAYOUT_SETTINGS_NOT_CONFIGURED` | Реквизиты ещё не заданы |

---

## `PUT /api/v1/organizations/{slug}/payout-settings`

Создаёт или обновляет банковские реквизиты. **Owner-only** (проверяется middleware `org.member:payouts.manage`). BankAccount шифруется через `Crypt` при сохранении; plaintext номер хранится только внутри `BankAccount` VO в памяти.

### Request

```http
PUT /api/v1/organizations/acme-salon/payout-settings
Authorization: Bearer <jwt>
Content-Type: application/json

{
  "bank_name": "Сбербанк",
  "account_number": "40702810123456786789",
  "account_holder": "ООО АКМЕ",
  "bic": "044525225",
  "payout_schedule": "weekly",
  "minimum_payout_cents": 100000
}
```

### Validation

| Поле | Правило |
|------|---------|
| `bank_name` | required, string, max 255 |
| `account_number` | required, string, 10–30 chars |
| `account_holder` | required, string, max 255 |
| `bic` | required, regex `^\d{9}$` (ровно 9 цифр) |
| `payout_schedule` | required, in: `weekly`, `biweekly`, `monthly`, `on_request` |
| `minimum_payout_cents` | required, integer, >= 0 |

### Response 200

Тот же формат, что `GET /payout-settings` (маскированный `account_number`).

### Errors

| HTTP | code | Причина |
|------|------|---------|
| 403  | `FORBIDDEN_INSUFFICIENT_ROLE` | Не owner |
| 404  | `ORGANIZATION_NOT_FOUND` | Slug не существует |
| 422  | `VALIDATION_ERROR` | Нарушены правила валидации |

### Side effects

Публикует `PayoutSettingsUpdated` (reliable=false) — **без чувствительных полей** в payload (account number не попадает в outbox/event log).

---

## `GET /api/v1/organizations/{slug}/stats`

Агрегированная статистика организации за последние 30 дней. Используется для Owner Dashboard.

### Request

```http
GET /api/v1/organizations/acme-salon/stats
Authorization: Bearer <jwt>
```

### Response 200

```json
{
  "success": true,
  "data": {
    "revenue_30d_cents": 4800000,
    "platform_fee_30d_cents": 480000,
    "net_payout_30d_cents": 4320000,
    "bookings_30d": 47,
    "conversion_rate": 0.83,
    "currency": "RUB"
  },
  "error": null,
  "meta": null
}
```

**Поля:**

- `revenue_30d_cents` — суммарный gross всех paid платежей за 30 дней.
- `platform_fee_30d_cents` — суммарная комиссия платформы за тот же период.
- `net_payout_30d_cents` — суммарный net к выплате провайдеру.
- `bookings_30d` — количество подтверждённых бронирований.
- `conversion_rate` — `confirmed / (confirmed + cancelled)` за период, 0.0–1.0.

### Errors

| HTTP | code | Причина |
|------|------|---------|
| 403  | `FORBIDDEN_INSUFFICIENT_ROLE` | Нет `analytics.view` |
| 404  | `ORGANIZATION_NOT_FOUND` | Slug не существует |

---

## Связанные документы

- [Payment Module README](../../backend/app/Modules/Payment/README.md)
- [Authentication](authentication.md)
- [Marketplace fee](../patterns/marketplace-fee.md)
- [Outbox Pattern](../patterns/outbox-pattern.md)
- [ADR-012: Organizations + Memberships](../adr/012-organizations-memberships.md)
- [ADR-013: Marketplace fee](../adr/013-marketplace-fee.md)
