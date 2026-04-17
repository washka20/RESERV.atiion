<?php

declare(strict_types=1);

use App\Modules\Booking\Domain\ValueObject\DateRange;

it('creates with valid checkIn and checkOut', function (): void {
    $checkIn = new DateTimeImmutable('2026-05-01');
    $checkOut = new DateTimeImmutable('2026-05-03');

    $range = new DateRange($checkIn, $checkOut);

    expect($range->checkIn)->toBe($checkIn);
    expect($range->checkOut)->toBe($checkOut);
});

it('rejects checkOut equal to checkIn', function (): void {
    $same = new DateTimeImmutable('2026-05-01');

    new DateRange($same, $same);
})->throws(InvalidArgumentException::class, 'checkOut must be after checkIn');

it('rejects checkOut before checkIn', function (): void {
    new DateRange(
        new DateTimeImmutable('2026-05-03'),
        new DateTimeImmutable('2026-05-01'),
    );
})->throws(InvalidArgumentException::class, 'checkOut must be after checkIn');

it('creates from strings via static factory', function (): void {
    $range = DateRange::fromStrings('2026-05-01', '2026-05-05');

    expect($range->checkIn->format('Y-m-d'))->toBe('2026-05-01');
    expect($range->checkOut->format('Y-m-d'))->toBe('2026-05-05');
});

it('calculates number of nights', function (): void {
    $range = DateRange::fromStrings('2026-05-01', '2026-05-04');

    expect($range->nights())->toBe(3);
});

it('detects overlapping date ranges', function (): void {
    $a = DateRange::fromStrings('2026-05-01', '2026-05-05');
    $b = DateRange::fromStrings('2026-05-04', '2026-05-08');

    expect($a->overlaps($b))->toBeTrue();
    expect($b->overlaps($a))->toBeTrue();
});

it('detects non-overlapping date ranges', function (): void {
    $a = DateRange::fromStrings('2026-05-01', '2026-05-03');
    $b = DateRange::fromStrings('2026-05-03', '2026-05-05');

    expect($a->overlaps($b))->toBeFalse();
});
