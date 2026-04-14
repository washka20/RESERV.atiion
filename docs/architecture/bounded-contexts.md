# Bounded Contexts

## Identity

**Purpose:** аутентификация, пользователи, роли, permissions.

**Aggregates:** User, Role
**Value Objects:** Email, HashedPassword, UserId, RoleId, FullName
**Events:** UserRegistered, UserRoleAssigned

**Зависит от:** ничего
**Публикует события, которые слушают:** все модули (для ownership checks)

## Catalog

**Purpose:** каталог услуг с типизацией (time slot / quantity).

**Aggregates:** Service, Category
**Entities:** Subcategory (child of Category)
**Value Objects:** ServiceId, ServiceType, Money, Duration, ImagePath
**Events:** ServiceCreated, ServiceUpdated, ServiceDeactivated

**Зависит от:** Identity (для ownership, если потребуется)
**Публикует события, которые слушают:** Booking (для валидации), Landing Builder

## Booking (core)

**Purpose:** бронирование, слоты, проверка доступности.

**Aggregates:** Booking, TimeSlot
**Value Objects:** BookingId, BookingStatus, BookingType, TimeRange, DateRange, Quantity, SlotId
**Specifications:** CancellationPolicy, BookingPolicy, AvailabilityRules
**Domain Services:** AvailabilityChecker со стратегиями (TimeSlotStrategy, QuantityStrategy)
**Events:** BookingCreated, BookingConfirmed, BookingCancelled, BookingCompleted

**Зависит от:** Catalog (через ServiceRepositoryInterface), Identity
**Публикует события, которые слушают:** Payment, Notifications

## Payment

**Purpose:** оплата (интерфейсы на старте, реализация позже).

**Aggregates:** Payment
**Value Objects:** PaymentId, PaymentStatus, PaymentMethod
**Interfaces:** PaymentGatewayInterface, PaymentProcessorInterface
**Stub:** NullPaymentGateway (auto-approve)

**Зависит от:** Booking (через события)
**Публикует события:** PaymentReceived, PaymentRefunded, PaymentFailed
