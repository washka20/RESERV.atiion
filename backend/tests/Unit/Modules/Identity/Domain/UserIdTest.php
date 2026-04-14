<?php

declare(strict_types=1);

use App\Modules\Identity\Domain\ValueObject\UserId;

it('generates valid uuid', function (): void {
    $id = UserId::generate();
    expect($id->toString())->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i');
});

it('equals same UserId with same uuid', function (): void {
    $uuid = '0191e8a4-4c1f-7e2a-9b3c-7f1a5d9e4b0d';
    expect((new UserId($uuid))->equals(new UserId($uuid)))->toBeTrue();
});
