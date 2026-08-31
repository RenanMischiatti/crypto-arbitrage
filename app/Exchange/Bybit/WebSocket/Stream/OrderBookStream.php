<?php

declare(strict_types=1);

namespace App\Exchange\Bybit\WebSocket\Stream;

final class OrderBookStream
{
    /**
     * @param list<string> $symbols
     */
    public function subscribe(array $symbols): string
    {
        if ($symbols === []) {
            throw new \InvalidArgumentException('At least one Bybit symbol must be configured.');
        }

        $topics = array_map(
            static function (string $symbol): string {
                $symbol = strtoupper(trim($symbol));

                if ($symbol === '' || preg_match('/^[A-Z0-9]+$/', $symbol) !== 1) {
                    throw new \InvalidArgumentException(sprintf('Invalid Bybit symbol: "%s".', $symbol));
                }

                return 'orderbook.1.' . $symbol;
            },
            $symbols
        );

        return json_encode([
            'op' => 'subscribe',
            'args' => $topics,
        ], JSON_THROW_ON_ERROR);
    }
}
