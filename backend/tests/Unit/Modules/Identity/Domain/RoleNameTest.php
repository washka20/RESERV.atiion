<?php

declare(strict_types=1);

use App\Modules\Identity\Domain\ValueObject\RoleName;

it('has three cases', function (): void {
    expect(RoleName::cases())->toHaveCount(3);
});

it('casts from string values', function (): void {
    expect(RoleName::from('admin'))->toBe(RoleName::Admin);
    expect(RoleName::from('manager'))->toBe(RoleName::Manager);
    expect(RoleName::from('user'))->toBe(RoleName::User);
});
