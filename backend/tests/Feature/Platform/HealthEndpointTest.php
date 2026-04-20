<?php

declare(strict_types=1);

use App\Modules\Platform\Application\HealthChecker;
use App\Modules\Platform\Domain\ValueObject\HealthStatus;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\Storage;

it('возвращает 200 healthy при всех рабочих dependencies', function (): void {
    Storage::fake('s3');

    $response = $this->getJson('/api/v1/health');

    $response->assertStatus(200)
        ->assertJson(['status' => 'healthy'])
        ->assertJsonStructure([
            'status',
            'checks' => ['database' => ['status'], 'cache' => ['status'], 'storage' => ['status']],
        ]);
});

it('не требует auth', function (): void {
    Storage::fake('s3');

    $response = $this->getJson('/api/v1/health');

    $response->assertStatus(200);
});

it('HealthChecker возвращает degraded когда cache падает но БД работает', function (): void {
    $db = Mockery::mock(Connection::class);
    $db->shouldReceive('select')->once()->andReturn([[1]]);

    $cache = Mockery::mock(CacheRepository::class);
    $cache->shouldReceive('put')->once()->andThrow(new RuntimeException('redis down'));

    $storage = Mockery::mock(Filesystem::class);
    $storage->shouldReceive('exists')->once()->andReturn(false);

    $checker = new HealthChecker($db, $cache, $storage);
    $result = $checker->check();

    expect($result['status'])->toBe(HealthStatus::DEGRADED);
    expect($result['checks']['cache']['status'])->toBe('error');
    expect($result['checks']['database']['status'])->toBe('ok');
});

it('HealthChecker возвращает unhealthy когда БД падает', function (): void {
    $db = Mockery::mock(Connection::class);
    $db->shouldReceive('select')->once()->andThrow(new RuntimeException('pg refused'));

    $cache = Mockery::mock(CacheRepository::class);
    $storage = Mockery::mock(Filesystem::class);

    $checker = new HealthChecker($db, $cache, $storage);
    $result = $checker->check();

    expect($result['status'])->toBe(HealthStatus::UNHEALTHY);
    expect($result['status']->httpStatus())->toBe(503);
});

it('HealthChecker возвращает healthy когда всё работает', function (): void {
    $db = Mockery::mock(Connection::class);
    $db->shouldReceive('select')->once()->andReturn([[1]]);

    $cache = Mockery::mock(CacheRepository::class);
    $cache->shouldReceive('put')->once();
    $cache->shouldReceive('get')->once()->andReturn('1');
    $cache->shouldReceive('forget')->once();

    $storage = Mockery::mock(Filesystem::class);
    $storage->shouldReceive('exists')->once()->andReturn(false);

    $checker = new HealthChecker($db, $cache, $storage);
    $result = $checker->check();

    expect($result['status'])->toBe(HealthStatus::HEALTHY);
    expect($result['status']->httpStatus())->toBe(200);
});
