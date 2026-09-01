<?php

declare(strict_types=1);

return [
    'exchanges' => [
        'binance',
        'bybit',
        'okx',
    ],
    'quote_ttl_seconds' => 5,
    'max_quote_age_ms' => 3000,
    'min_spread_percent' => '0.30',
    'sleep_after_opportunity_seconds' => 10,
];
