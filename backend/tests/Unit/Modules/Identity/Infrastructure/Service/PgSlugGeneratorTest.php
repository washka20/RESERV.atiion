<?php

declare(strict_types=1);

use App\Modules\Identity\Domain\Repository\OrganizationRepositoryInterface;
use App\Modules\Identity\Domain\ValueObject\OrganizationSlug;
use App\Modules\Identity\Infrastructure\Service\PgSlugGenerator;

it('generates slug from latin source', function (): void {
    $repo = mock(OrganizationRepositoryInterface::class);
    $repo->shouldReceive('existsBySlug')->andReturnFalse();

    $gen = new PgSlugGenerator($repo);

    $slug = $gen->generate('Salon Savvin');

    expect($slug->toString())->toBe('salon-savvin');
});

it('strips diacritics', function (): void {
    $repo = mock(OrganizationRepositoryInterface::class);
    $repo->shouldReceive('existsBySlug')->andReturnFalse();

    $gen = new PgSlugGenerator($repo);

    $slug = $gen->generate('Café Crème');

    expect($slug->toString())->toBe('cafe-creme');
});

it('transliterates cyrillic to latin', function (): void {
    $repo = mock(OrganizationRepositoryInterface::class);
    $repo->shouldReceive('existsBySlug')->andReturnFalse();

    $gen = new PgSlugGenerator($repo);

    $slug = $gen->generate('Солнце и Песок');

    expect($slug->toString())->toMatch('/^[a-z0-9-]+$/');
    expect($slug->toString())->toContain('solntse');
    expect($slug->toString())->toContain('pesok');
});

it('pads short slug up to minimum length', function (): void {
    $repo = mock(OrganizationRepositoryInterface::class);
    $repo->shouldReceive('existsBySlug')->andReturnFalse();

    $gen = new PgSlugGenerator($repo);

    $slug = $gen->generate('ab');

    expect(strlen($slug->toString()))->toBeGreaterThanOrEqual(3);
    expect($slug->toString())->toStartWith('ab');
});

it('pads empty source with random suffix', function (): void {
    $repo = mock(OrganizationRepositoryInterface::class);
    $repo->shouldReceive('existsBySlug')->andReturnFalse();

    $gen = new PgSlugGenerator($repo);

    $slug = $gen->generate('!!!');

    expect(strlen($slug->toString()))->toBeGreaterThanOrEqual(3);
    expect($slug->toString())->toMatch('/^[a-z0-9][a-z0-9-]*[a-z0-9]$/');
});

it('appends -2 suffix on first collision', function (): void {
    $repo = mock(OrganizationRepositoryInterface::class);
    $repo->shouldReceive('existsBySlug')
        ->with(Mockery::on(fn (OrganizationSlug $s) => $s->toString() === 'salon-savvin'))
        ->andReturnTrue();
    $repo->shouldReceive('existsBySlug')
        ->with(Mockery::on(fn (OrganizationSlug $s) => $s->toString() === 'salon-savvin-2'))
        ->andReturnFalse();

    $gen = new PgSlugGenerator($repo);

    $slug = $gen->generate('Salon Savvin');

    expect($slug->toString())->toBe('salon-savvin-2');
});

it('increments suffix through multiple collisions', function (): void {
    $repo = mock(OrganizationRepositoryInterface::class);
    $repo->shouldReceive('existsBySlug')->andReturnUsing(function (OrganizationSlug $s): bool {
        return in_array($s->toString(), [
            'salon-savvin',
            'salon-savvin-2',
            'salon-savvin-3',
            'salon-savvin-4',
        ], true);
    });

    $gen = new PgSlugGenerator($repo);

    $slug = $gen->generate('Salon Savvin');

    expect($slug->toString())->toBe('salon-savvin-5');
});

it('collapses double dashes and trims punctuation', function (): void {
    $repo = mock(OrganizationRepositoryInterface::class);
    $repo->shouldReceive('existsBySlug')->andReturnFalse();

    $gen = new PgSlugGenerator($repo);

    $slug = $gen->generate('  Hello -- World!!!  ');

    expect($slug->toString())->toBe('hello-world');
    expect($slug->toString())->not->toContain('--');
});

it('truncates long source to fit max slug length', function (): void {
    $repo = mock(OrganizationRepositoryInterface::class);
    $repo->shouldReceive('existsBySlug')->andReturnFalse();

    $gen = new PgSlugGenerator($repo);

    $long = str_repeat('salon-savvin ', 20);
    $slug = $gen->generate($long);

    expect(strlen($slug->toString()))->toBeLessThanOrEqual(64);
});
