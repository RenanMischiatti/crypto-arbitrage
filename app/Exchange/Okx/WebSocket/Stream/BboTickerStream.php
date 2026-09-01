<?php

declare(strict_types=1);

namespace App\Exchange\Okx\WebSocket\Stream;

final class BboTickerStream
{
    /** @param list<string> $symbols */
    public function subscribe(array $symbols): string
    {
        if ($symbols === []) {
            throw new \InvalidArgumentException('At least one OKX symbol must be configured.');
        }

        $topics = array_map(
            static function (string $symbol): array {
                $symbol = strtoupper(trim($symbol));

                if (preg_match('/^([A-Z0-9]+)(USDT)$/', $symbol, $matches) !== 1) {
                    throw new \InvalidArgumentException(sprintf('Invalid OKX symbol: "%s".', $symbol));
                }

                return [
                    'channel' => 'bbo-tbt',
                    'instId' => $matches[1] . '-' . $matches[2],
                ];
            },
            $symbols
        );

        return json_encode([
            'op' => 'subscribe',
            'args' => $topics,
        ], JSON_THROW_ON_ERROR);
    }
}
