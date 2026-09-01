<?php

declare(strict_types=1);

return [
    'exchanges' => [
        'binance',
        'bybit',
    ],
    'quote_ttl_seconds' => 5,
    'max_quote_age_ms' => 3000,
    'min_spread_percent' => '0.10',
    'min_logged_spread_percent' => '0.20',
    'sleep_after_opportunity_seconds' => 10,
];
