<?php

declare(strict_types=1);

namespace App\Modules\Catalog;

use App\Modules\Catalog\Domain\Repository\CategoryRepositoryInterface;
use App\Modules\Catalog\Domain\Repository\ServiceRepositoryInterface;
use App\Modules\Catalog\Infrastructure\Persistence\Repository\EloquentCategoryRepository;
use App\Modules\Catalog\Infrastructure\Persistence\Repository\EloquentServiceRepository;
use Illuminate\Support\ServiceProvider;

final class Provider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ServiceRepositoryInterface::class, EloquentServiceRepository::class);
        $this->app->bind(CategoryRepositoryInterface::class, EloquentCategoryRepository::class);
    }

    public function boot(): void {}
}
