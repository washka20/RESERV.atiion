<?php

declare(strict_types=1);

use App\Modules\Identity\Application\Service\JwtTokenServiceInterface;
use App\Modules\Identity\Domain\Exception\InvalidCredentialsException;
use App\Modules\Identity\Domain\ValueObject\UserId;
use App\Modules\Identity\Infrastructure\Auth\JwtTokenService;
use App\Modules\Identity\Infrastructure\Persistence\Model\RefreshTokenModel;
use App\Modules\Identity\Infrastructure\Persistence\Model\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Lcobucci\Clock\SystemClock;

uses(RefreshDatabase::class);

function makeService(): JwtTokenServiceInterface
{
    return new JwtTokenService(
        secret: str_repeat('s', 64),
        issuer: 'http://localhost',
        audience: 'reservatiion-customer',
        accessTtl: 3600,
        refreshTtl: 2592000,
        clock: SystemClock::fromUTC(),
    );
}

function makeUserInDb(): UserId
{
    $id = UserId::generate();
    UserModel::create([
        'id' => $id->toString(),
        'email' => 'a@b.com',
        'first_name' => 'A',
        'last_name' => 'B',
        'password' => 'hash',
    ]);

    return $id;
}

it('issues valid access and refresh tokens', function (): void {
    $service = makeService();
    $userId = makeUserInDb();

    $pair = $service->issue($userId);

    expect($pair->accessToken)->toBeString()->not->toBeEmpty();
    expect($pair->refreshToken)->toBeString()->not->toBeEmpty();
    expect($pair->expiresIn)->toBe(3600);

    $claims = $service->parseAccess($pair->accessToken);
    expect($claims->userId->equals($userId))->toBeTrue();
});

it('stores refresh token hash, not plain', function (): void {
    $service = makeService();
    $userId = makeUserInDb();

    $pair = $service->issue($userId);

    $stored = RefreshTokenModel::where('user_id', $userId->toString())->first();
    expect($stored)->not->toBeNull();
    expect($stored->token_hash)->toBe(hash('sha256', $pair->refreshToken));
    expect($stored->token_hash)->not->toBe($pair->refreshToken);
});

it('refresh rotates refresh token and revokes old', function (): void {
    $service = makeService();
    $userId = makeUserInDb();

    $first = $service->issue($userId);
    $second = $service->refresh($first->refreshToken);

    expect($second->refreshToken)->not->toBe($first->refreshToken);

    $oldHash = hash('sha256', $first->refreshToken);
    $oldRecord = RefreshTokenModel::where('token_hash', $oldHash)->first();
    expect($oldRecord->revoked_at)->not->toBeNull();
});

it('parseAccess throws on invalid token', function (): void {
    $service = makeService();
    $service->parseAccess('not-a-jwt');
})->throws(InvalidCredentialsException::class);

it('refresh with revoked token throws', function (): void {
    $service = makeService();
    $userId = makeUserInDb();

    $pair = $service->issue($userId);
    $service->revoke($pair->refreshToken);

    $service->refresh($pair->refreshToken);
})->throws(InvalidCredentialsException::class);

it('refresh with unknown token throws', function (): void {
    $service = makeService();
    $service->refresh('unknown-token');
})->throws(InvalidCredentialsException::class);

it('revoke marks refresh token as revoked', function (): void {
    $service = makeService();
    $userId = makeUserInDb();

    $pair = $service->issue($userId);
    $service->revoke($pair->refreshToken);

    $hash = hash('sha256', $pair->refreshToken);
    $record = RefreshTokenModel::where('token_hash', $hash)->first();
    expect($record->revoked_at)->not->toBeNull();
});
