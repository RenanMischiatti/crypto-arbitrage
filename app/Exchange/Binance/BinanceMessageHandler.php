<?php

declare(strict_types=1);

namespace App\Exchange\Binance;

use App\Domain\MarketData\DTO\Quote;

final class BinanceMessageHandler
{
    public function handle(string $message): Quote
    {
        $payload = json_decode(
            $message,
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        if (! is_array($payload)) {
            throw new \UnexpectedValueException('Invalid Binance WebSocket payload.');
        }

        $data = $payload['data'] ?? null;

        if (! is_array($data) || ! isset($data['s'], $data['b'], $data['B'], $data['a'], $data['A'])) {
            throw new \UnexpectedValueException('Invalid Binance bookTicker message.');
        }

        return new Quote(
            symbol: (string) $data['s'],
            bidPrice: (string) $data['b'],
            bidQuantity: (string) $data['B'],
            askPrice: (string) $data['a'],
            askQuantity: (string) $data['A'],
            updateId: isset($data['u']) ? (int) $data['u'] : null,
        );
    }
}
