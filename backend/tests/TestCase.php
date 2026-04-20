<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Spatie\Permission\PermissionRegistrar;

abstract class TestCase extends BaseTestCase
{
    /**
     * Spatie PermissionRegistrar кэширует permissions в статическом поле
     * worker-процесса. Paratest каждый тест делает migrate:fresh, но cache
     * остаётся с устаревшими permission_id → PermissionDoesNotExist при
     * role->givePermissionTo().
     *
     * Сбрасываем кэш перед каждым тестом, чтобы permissions подгружались
     * заново из свежемигрированной БД.
     */
    protected function setUp(): void
    {
        parent::setUp();

        if ($this->app->bound(PermissionRegistrar::class)) {
            $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();
        }
    }
}
