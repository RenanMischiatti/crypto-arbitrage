<?php

declare(strict_types=1);

namespace App\Exchange\Bybit;

use App\Domain\MarketData\DTO\Quote;

final class BybitMessageHandler
{
    public function handle(string $message): ?Quote
    {
        $payload = json_decode($message, true, 512, JSON_THROW_ON_ERROR);

        if (! is_array($payload)) {
            throw new \UnexpectedValueException('Invalid Bybit WebSocket payload.');
        }

        if (isset($payload['op'])) {
            if (($payload['success'] ?? true) === false) {
                throw new \RuntimeException('Bybit rejected the WebSocket request.');
            }

            return null;
        }

        $data = $payload['data'] ?? null;
        $bid = is_array($data) ? ($data['b'][0] ?? null) : null;
        $ask = is_array($data) ? ($data['a'][0] ?? null) : null;

        if (
            ! is_array($data)
            || ! isset($data['s'])
            || ! is_array($bid)
            || ! isset($bid[0], $bid[1])
            || ! is_array($ask)
            || ! isset($ask[0], $ask[1])
        ) {
            throw new \UnexpectedValueException('Invalid Bybit orderbook message.');
        }

        return new Quote(
            symbol: (string) $data['s'],
            bidPrice: (string) $bid[0],
            bidQuantity: (string) $bid[1],
            askPrice: (string) $ask[0],
            askQuantity: (string) $ask[1],
            updateId: isset($data['u']) ? (int) $data['u'] : null,
        );
    }
}
