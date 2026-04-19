<?php

declare(strict_types=1);

use App\Modules\Identity\Application\Service\AuthService;
use App\Modules\Identity\Application\Service\JwtTokenServiceInterface;
use App\Modules\Identity\Application\Service\TokenPair;
use App\Modules\Identity\Application\Service\UserMembershipsLookupInterface;
use App\Modules\Identity\Domain\Entity\User;
use App\Modules\Identity\Domain\Exception\InvalidCredentialsException;
use App\Modules\Identity\Domain\Repository\UserRepositoryInterface;
use App\Modules\Identity\Domain\ValueObject\Email;
use App\Modules\Identity\Domain\ValueObject\FullName;
use App\Modules\Identity\Domain\ValueObject\HashedPassword;
use App\Modules\Identity\Domain\ValueObject\UserId;
use Tests\Unit\Modules\Identity\Application\Support\InMemoryPasswordHasher;

/**
 * Стабовый UserMembershipsLookup — AuthService использует его для fetch memberships
 * перед issue. В юнит-тестах достаточно always-empty lookup'а.
 */
function stubMembershipsHandler(): UserMembershipsLookupInterface
{
    $mock = Mockery::mock(UserMembershipsLookupInterface::class);
    $mock->shouldReceive('forUser')->andReturn([]);

    return $mock;
}

it('returns TokenPair on successful login', function (): void {
    $users = Mockery::mock(UserRepositoryInterface::class);
    $jwt = Mockery::mock(JwtTokenServiceInterface::class);
    $hasher = new InMemoryPasswordHasher;

    $userId = UserId::generate();
    $user = User::register($userId, new Email('a@b.com'), HashedPassword::fromPlaintext('secret', $hasher), new FullName('A', 'B', null));
    $user->pullDomainEvents();

    $users->shouldReceive('findByEmail')->once()->andReturn($user);
    $jwt->shouldReceive('issue')
        ->once()
        ->with(Mockery::on(fn ($id) => $id instanceof UserId), Mockery::on(fn ($claims) => isset($claims['memberships']) && $claims['memberships'] === []))
        ->andReturn(new TokenPair('acc', 'ref', 3600));

    $auth = new AuthService($users, $hasher, $jwt, stubMembershipsHandler());
    $result = $auth->login('a@b.com', 'secret');

    expect($result)->toBeInstanceOf(TokenPair::class);
    expect($result->accessToken)->toBe('acc');
});

it('throws InvalidCredentialsException on wrong password', function (): void {
    $users = Mockery::mock(UserRepositoryInterface::class);
    $jwt = Mockery::mock(JwtTokenServiceInterface::class);
    $hasher = new InMemoryPasswordHasher;

    $userId = UserId::generate();
    $user = User::register($userId, new Email('a@b.com'), HashedPassword::fromPlaintext('secret', $hasher), new FullName('A', 'B', null));
    $user->pullDomainEvents();

    $users->shouldReceive('findByEmail')->once()->andReturn($user);

    $auth = new AuthService($users, $hasher, $jwt, stubMembershipsHandler());
    $auth->login('a@b.com', 'wrong-password');
})->throws(InvalidCredentialsException::class);

it('throws InvalidCredentialsException when user not found', function (): void {
    $users = Mockery::mock(UserRepositoryInterface::class);
    $jwt = Mockery::mock(JwtTokenServiceInterface::class);
    $hasher = new InMemoryPasswordHasher;

    $users->shouldReceive('findByEmail')->once()->andReturnNull();

    $auth = new AuthService($users, $hasher, $jwt, stubMembershipsHandler());
    $auth->login('nonexistent@b.com', 'password');
})->throws(InvalidCredentialsException::class);

it('throws InvalidCredentialsException on invalid email format', function (): void {
    $users = Mockery::mock(UserRepositoryInterface::class);
    $jwt = Mockery::mock(JwtTokenServiceInterface::class);
    $hasher = new InMemoryPasswordHasher;

    $auth = new AuthService($users, $hasher, $jwt, stubMembershipsHandler());
    $auth->login('not-an-email', 'password');
})->throws(InvalidCredentialsException::class);

it('rotates refresh and re-fetches memberships before issue', function (): void {
    $users = Mockery::mock(UserRepositoryInterface::class);
    $jwt = Mockery::mock(JwtTokenServiceInterface::class);
    $hasher = new InMemoryPasswordHasher;

    $userId = UserId::generate();
    $jwt->shouldReceive('rotateRefresh')->once()->with('refresh-token')->andReturn($userId);
    $jwt->shouldReceive('issue')
        ->once()
        ->with(Mockery::on(fn ($id) => $id instanceof UserId && $id->equals($userId)), Mockery::on(fn ($claims) => array_key_exists('memberships', $claims)))
        ->andReturn(new TokenPair('new-acc', 'new-ref', 3600));

    $auth = new AuthService($users, $hasher, $jwt, stubMembershipsHandler());
    $result = $auth->refresh('refresh-token');

    expect($result->accessToken)->toBe('new-acc');
});

it('delegates logout to JwtTokenServiceInterface::revoke', function (): void {
    $users = Mockery::mock(UserRepositoryInterface::class);
    $jwt = Mockery::mock(JwtTokenServiceInterface::class);
    $hasher = new InMemoryPasswordHasher;

    $jwt->shouldReceive('revoke')->once()->with('refresh-token');

    $auth = new AuthService($users, $hasher, $jwt, stubMembershipsHandler());
    $auth->logout('refresh-token');
});
