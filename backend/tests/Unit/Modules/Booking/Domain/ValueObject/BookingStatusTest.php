<?php

declare(strict_types=1);

use App\Modules\Booking\Domain\ValueObject\BookingStatus;

it('isActive returns true for active statuses', function (BookingStatus $status): void {
    expect($status->isActive())->toBeTrue();
})->with([
    'pending' => BookingStatus::PENDING,
    'confirmed' => BookingStatus::CONFIRMED,
]);

it('isActive returns false for final statuses', function (BookingStatus $status): void {
    expect($status->isActive())->toBeFalse();
})->with([
    'cancelled' => BookingStatus::CANCELLED,
    'completed' => BookingStatus::COMPLETED,
]);

it('isFinal returns true for final statuses', function (BookingStatus $status): void {
    expect($status->isFinal())->toBeTrue();
})->with([
    'cancelled' => BookingStatus::CANCELLED,
    'completed' => BookingStatus::COMPLETED,
]);

it('isFinal returns false for active statuses', function (BookingStatus $status): void {
    expect($status->isFinal())->toBeFalse();
})->with([
    'pending' => BookingStatus::PENDING,
    'confirmed' => BookingStatus::CONFIRMED,
]);

it('creates from string value', function (): void {
    expect(BookingStatus::from('pending'))->toBe(BookingStatus::PENDING);
    expect(BookingStatus::from('confirmed'))->toBe(BookingStatus::CONFIRMED);
    expect(BookingStatus::from('cancelled'))->toBe(BookingStatus::CANCELLED);
    expect(BookingStatus::from('completed'))->toBe(BookingStatus::COMPLETED);
});
