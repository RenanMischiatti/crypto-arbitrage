<?php

declare(strict_types=1);

namespace App\Exchange\Binance\WebSocket\Stream;

final class BookTickerStream
{
    /**
     * @param list<string> $symbols
     */
    public function build(array $symbols): string
    {
        if ($symbols === []) {
            throw new \InvalidArgumentException('At least one Binance symbol must be configured.');
        }

        $streams = array_map(
            static function (string $symbol): string {
                $symbol = trim($symbol);

                if ($symbol === '' || preg_match('/^[A-Z0-9]+$/i', $symbol) !== 1) {
                    throw new \InvalidArgumentException(sprintf('Invalid Binance symbol: "%s".', $symbol));
                }

                return strtolower($symbol) . '@bookTicker';
            },
            $symbols
        );

        return '/stream?streams=' . implode('/', $streams);
    }
}
