<?php

declare(strict_types=1);

use App\Modules\Identity\Domain\ValueObject\CancellationPolicy;

it('maps policy to min hours before start', function (CancellationPolicy $policy, int $expected): void {
    expect($policy->minHoursBefore())->toBe($expected);
})->with([
    'flexible' => [CancellationPolicy::FLEXIBLE, 1],
    'moderate' => [CancellationPolicy::MODERATE, 24],
    'strict' => [CancellationPolicy::STRICT, 72],
]);

it('creates from string', function (): void {
    expect(CancellationPolicy::from('flexible'))->toBe(CancellationPolicy::FLEXIBLE);
    expect(CancellationPolicy::from('moderate'))->toBe(CancellationPolicy::MODERATE);
    expect(CancellationPolicy::from('strict'))->toBe(CancellationPolicy::STRICT);
});
