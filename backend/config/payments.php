<?php

declare(strict_types=1);
use App\Modules\Payment\Infrastructure\Gateway\NullPaymentGateway;

return [
    'default_gateway' => env('PAYMENT_GATEWAY', 'null'),

    'gateways' => [
        'null' => NullPaymentGateway::class,
    ],

    'marketplace_fee_percent' => (int) env('MARKETPLACE_FEE_PERCENT', 10),
    'default_currency' => env('PAYMENT_CURRENCY', 'RUB'),
    'log_channel' => env('PAYMENT_LOG_CHANNEL', 'payments'),

    'payouts' => [
        'log_channel' => env('PAYOUT_LOG_CHANNEL', 'payouts'),
        'default_minimum_cents' => 100000,
        'default_schedule' => 'weekly',
        'worker_batch_size' => 100,
    ],

    'outbox' => [
        'worker_batch_size' => 50,
        'max_retries' => 10,
    ],
];
