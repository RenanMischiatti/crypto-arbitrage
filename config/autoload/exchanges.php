<?php

declare(strict_types=1);

use function Hyperf\Support\env;

return [
    'binance' => [
        'websocket' => [
            'host' => (string) env('BINANCE_WEBSOCKET_HOST', 'stream.binance.com'),
            'port' => (int) env('BINANCE_WEBSOCKET_PORT', 9443),
            'ssl' => true,
            'reconnect_delay_seconds' => 10.0,
        ],
        'symbols' => [
            'BTCUSDT',
            'ETHUSDT',
            'SOLUSDT',
        ],
    ],
    'bybit' => [
        'websocket' => [
            'host' => (string) env('BYBIT_WEBSOCKET_HOST', 'stream.bybit.com'),
            'port' => (int) env('BYBIT_WEBSOCKET_PORT', 443),
            'path' => '/v5/public/spot',
            'ssl' => true,
            'ping_interval_seconds' => 20.0,
            'reconnect_delay_seconds' => 10.0,
        ],
        'symbols' => [
            'BTCUSDT',
            'ETHUSDT',
            'SOLUSDT',
        ],
    ],
];
