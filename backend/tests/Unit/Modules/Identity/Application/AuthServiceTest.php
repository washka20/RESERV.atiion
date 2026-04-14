<?php

declare(strict_types=1);

use App\Modules\Identity\Application\Service\AuthService;
use App\Modules\Identity\Application\Service\JwtTokenServiceInterface;
use App\Modules\Identity\Application\Service\TokenPair;
use App\Modules\Identity\Domain\Entity\User;
use App\Modules\Identity\Domain\Exception\InvalidCredentialsException;
use App\Modules\Identity\Domain\Repository\UserRepositoryInterface;
use App\Modules\Identity\Domain\ValueObject\Email;
use App\Modules\Identity\Domain\ValueObject\FullName;
use App\Modules\Identity\Domain\ValueObject\HashedPassword;
use App\Modules\Identity\Domain\ValueObject\UserId;
use Tests\Unit\Modules\Identity\Application\Support\InMemoryPasswordHasher;

it('returns TokenPair on successful login', function (): void {
    $users = Mockery::mock(UserRepositoryInterface::class);
    $jwt = Mockery::mock(JwtTokenServiceInterface::class);
    $hasher = new InMemoryPasswordHasher;

    $userId = UserId::generate();
    $user = User::register($userId, new Email('a@b.com'), HashedPassword::fromPlaintext('secret', $hasher), new FullName('A', 'B', null));
    $user->pullDomainEvents();

    $users->shouldReceive('findByEmail')->once()->andReturn($user);
    $jwt->shouldReceive('issue')->once()->with(Mockery::on(fn ($id) => $id instanceof UserId))->andReturn(new TokenPair('acc', 'ref', 3600));

    $auth = new AuthService($users, $hasher, $jwt);
    $result = $auth->login('a@b.com', 'secret');

    expect($result)->toBeInstanceOf(TokenPair::class);
    expect($result->accessToken)->toBe('acc');
    expect($result->refreshToken)->toBe('ref');
    expect($result->expiresIn)->toBe(3600);
});

it('throws InvalidCredentialsException on wrong password', function (): void {
    $users = Mockery::mock(UserRepositoryInterface::class);
    $jwt = Mockery::mock(JwtTokenServiceInterface::class);
    $hasher = new InMemoryPasswordHasher;

    $userId = UserId::generate();
    $user = User::register($userId, new Email('a@b.com'), HashedPassword::fromPlaintext('secret', $hasher), new FullName('A', 'B', null));
    $user->pullDomainEvents();

    $users->shouldReceive('findByEmail')->once()->andReturn($user);

    $auth = new AuthService($users, $hasher, $jwt);
    $auth->login('a@b.com', 'wrong-password');
})->throws(InvalidCredentialsException::class);

it('throws InvalidCredentialsException when user not found', function (): void {
    $users = Mockery::mock(UserRepositoryInterface::class);
    $jwt = Mockery::mock(JwtTokenServiceInterface::class);
    $hasher = new InMemoryPasswordHasher;

    $users->shouldReceive('findByEmail')->once()->andReturnNull();

    $auth = new AuthService($users, $hasher, $jwt);
    $auth->login('nonexistent@b.com', 'password');
})->throws(InvalidCredentialsException::class);

it('throws InvalidCredentialsException on invalid email format', function (): void {
    $users = Mockery::mock(UserRepositoryInterface::class);
    $jwt = Mockery::mock(JwtTokenServiceInterface::class);
    $hasher = new InMemoryPasswordHasher;

    $auth = new AuthService($users, $hasher, $jwt);
    $auth->login('not-an-email', 'password');
})->throws(InvalidCredentialsException::class);

it('delegates refresh to JwtTokenServiceInterface', function (): void {
    $users = Mockery::mock(UserRepositoryInterface::class);
    $jwt = Mockery::mock(JwtTokenServiceInterface::class);
    $hasher = new InMemoryPasswordHasher;

    $jwt->shouldReceive('refresh')->once()->with('refresh-token')->andReturn(new TokenPair('new-acc', 'new-ref', 3600));

    $auth = new AuthService($users, $hasher, $jwt);
    $result = $auth->refresh('refresh-token');

    expect($result->accessToken)->toBe('new-acc');
});

it('delegates logout to JwtTokenServiceInterface::revoke', function (): void {
    $users = Mockery::mock(UserRepositoryInterface::class);
    $jwt = Mockery::mock(JwtTokenServiceInterface::class);
    $hasher = new InMemoryPasswordHasher;

    $jwt->shouldReceive('revoke')->once()->with('refresh-token');

    $auth = new AuthService($users, $hasher, $jwt);
    $auth->logout('refresh-token');
});
