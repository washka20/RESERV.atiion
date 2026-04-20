<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Media;

use App\Shared\Application\Media\MediaStorageInterface;
use Illuminate\Support\ServiceProvider;

/**
 * Регистрирует MediaStorageInterface → S3MediaStorage.
 *
 * По ADR-017: без cached decorator'а на старте. Добавим когда profiling
 * покажет реальный RPS и latency signed-url generation'а.
 */
final class MediaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(S3MediaStorage::class);
        $this->app->singleton(MediaStorageInterface::class, S3MediaStorage::class);
    }
}
