<?php

declare(strict_types=1);

use App\Modules\Booking\Domain\Specification\QuantityIsAvailable;

it('checks quantity availability', function (int $booked, int $requested, int $total, bool $expected): void {
    $spec = new QuantityIsAvailable;
    $ctx = ['booked' => $booked, 'requested' => $requested, 'total' => $total];

    expect($spec->isSatisfiedBy($ctx))->toBe($expected);
})->with([
    'empty room' => [0, 1, 10, true],
    'exact fit' => [5, 5, 10, true],
    'overflow by 1' => [5, 6, 10, false],
    'full booked' => [10, 1, 10, false],
    'single unit available' => [9, 1, 10, true],
]);

it('rejects non-array candidate', function (): void {
    $spec = new QuantityIsAvailable;

    expect($spec->isSatisfiedBy('bad'))->toBeFalse();
    expect($spec->failureReason())->toContain('array');
});

it('rejects invalid context shape', function (): void {
    $spec = new QuantityIsAvailable;

    expect($spec->isSatisfiedBy(['booked' => -1, 'requested' => 1, 'total' => 5]))->toBeFalse();
    expect($spec->failureReason())->toContain('invalid');
});
