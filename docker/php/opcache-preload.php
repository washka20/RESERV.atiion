<?php

declare(strict_types=1);

/**
 * Opcache preload для Laravel в production.
 *
 * Подключает autoload + базовые фреймворковые файлы в opcache shared memory —
 * каждый FPM worker стартует с уже загруженными классами. Замеры: cold start
 * route → response сокращается в 2-3 раза.
 *
 * Подключается через `opcache.preload = /usr/local/etc/php/opcache-preload.php`
 * в php-prod.ini, запускается от www-data (opcache.preload_user).
 *
 * См. https://laravel.com/docs/12.x/deployment#optimizing-package-autoload
 */
$appPath = '/var/www/html';

require $appPath.'/vendor/autoload.php';

// Warm up основные Laravel классы — вендор composer autoload уже предвключил
// все PSR-4 autoloads, но не прогрел opcache для tight loop классов.
$classesToPreload = [
    'Illuminate\\Foundation\\Application',
    'Illuminate\\Http\\Request',
    'Illuminate\\Http\\Response',
    'Illuminate\\Http\\JsonResponse',
    'Illuminate\\Routing\\Router',
    'Illuminate\\Routing\\RouteCollection',
    'Illuminate\\Database\\Connection',
    'Illuminate\\Database\\Query\\Builder',
    'Illuminate\\Support\\Collection',
    'Illuminate\\Support\\Str',
    'Illuminate\\Contracts\\Support\\Arrayable',
    'Illuminate\\Contracts\\Support\\Jsonable',
    'Carbon\\Carbon',
    'Carbon\\CarbonImmutable',
];

foreach ($classesToPreload as $class) {
    if (! class_exists($class) && ! interface_exists($class) && ! trait_exists($class)) {
        continue;
    }
    opcache_compile_file((new ReflectionClass($class))->getFileName());
}
