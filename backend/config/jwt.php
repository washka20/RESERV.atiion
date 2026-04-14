<?php

declare(strict_types=1);

return [
    'secret' => env('JWT_SECRET', ''),
    'ttl' => (int) env('JWT_TTL', 3600),
    'refresh_ttl' => (int) env('JWT_REFRESH_TTL', 60 * 60 * 24 * 30),
    'issuer' => env('APP_URL', 'http://localhost'),
    'audience' => 'reservatiion-customer',
    'algorithm' => 'HS256',
];
