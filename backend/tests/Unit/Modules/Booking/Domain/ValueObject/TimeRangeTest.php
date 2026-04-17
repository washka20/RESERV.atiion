<?php

declare(strict_types=1);

use App\Modules\Booking\Domain\ValueObject\TimeRange;

it('creates a time range with valid start and end', function (): void {
    $start = new DateTimeImmutable('2026-05-01 10:00:00');
    $end = new DateTimeImmutable('2026-05-01 11:00:00');

    $range = new TimeRange($start, $end);

    expect($range->startAt)->toBe($start);
    expect($range->endAt)->toBe($end);
});

it('rejects end equal to start', function (): void {
    $same = new DateTimeImmutable('2026-05-01 10:00:00');

    new TimeRange($same, $same);
})->throws(InvalidArgumentException::class, 'endAt must be strictly greater than startAt');

it('rejects end before start', function (): void {
    $start = new DateTimeImmutable('2026-05-01 11:00:00');
    $end = new DateTimeImmutable('2026-05-01 10:00:00');

    new TimeRange($start, $end);
})->throws(InvalidArgumentException::class, 'endAt must be strictly greater than startAt');

it('calculates duration in minutes', function (): void {
    $range = new TimeRange(
        new DateTimeImmutable('2026-05-01 10:00:00'),
        new DateTimeImmutable('2026-05-01 11:30:00'),
    );

    expect($range->durationInMinutes())->toBe(90);
});

it('detects overlapping ranges', function (): void {
    $a = new TimeRange(
        new DateTimeImmutable('2026-05-01 10:00:00'),
        new DateTimeImmutable('2026-05-01 12:00:00'),
    );
    $b = new TimeRange(
        new DateTimeImmutable('2026-05-01 11:00:00'),
        new DateTimeImmutable('2026-05-01 13:00:00'),
    );

    expect($a->overlaps($b))->toBeTrue();
    expect($b->overlaps($a))->toBeTrue();
});

it('detects non-overlapping ranges', function (): void {
    $a = new TimeRange(
        new DateTimeImmutable('2026-05-01 10:00:00'),
        new DateTimeImmutable('2026-05-01 11:00:00'),
    );
    $b = new TimeRange(
        new DateTimeImmutable('2026-05-01 11:00:00'),
        new DateTimeImmutable('2026-05-01 12:00:00'),
    );

    expect($a->overlaps($b))->toBeFalse();
});

it('checks if a moment is contained within range', function (): void {
    $range = new TimeRange(
        new DateTimeImmutable('2026-05-01 10:00:00'),
        new DateTimeImmutable('2026-05-01 12:00:00'),
    );

    expect($range->contains(new DateTimeImmutable('2026-05-01 11:00:00')))->toBeTrue();
    expect($range->contains(new DateTimeImmutable('2026-05-01 10:00:00')))->toBeTrue();
    expect($range->contains(new DateTimeImmutable('2026-05-01 12:00:00')))->toBeFalse();
    expect($range->contains(new DateTimeImmutable('2026-05-01 09:59:59')))->toBeFalse();
});
