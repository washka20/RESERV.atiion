<?php

declare(strict_types=1);

use App\Modules\Booking\Domain\Specification\UserNotExceedsLimit;

it('allows when active < limit', function (): void {
    $spec = new UserNotExceedsLimit;

    expect($spec->isSatisfiedBy(['userActiveBookings' => 3, 'limit' => 5]))->toBeTrue();
});

it('rejects when active == limit', function (): void {
    $spec = new UserNotExceedsLimit;

    expect($spec->isSatisfiedBy(['userActiveBookings' => 5, 'limit' => 5]))->toBeFalse();
    expect($spec->failureReason())->toContain('limit');
});

it('rejects when active > limit', function (): void {
    $spec = new UserNotExceedsLimit;

    expect($spec->isSatisfiedBy(['userActiveBookings' => 10, 'limit' => 5]))->toBeFalse();
});
