<?php

declare(strict_types=1);

namespace App\Exchange\Okx;

use App\Domain\MarketData\DTO\Quote;

final class OkxMessageHandler
{
    public function handle(string $message): ?Quote
    {
        $payload = json_decode($message, true, 512, JSON_THROW_ON_ERROR);

        if (! is_array($payload)) {
            throw new \UnexpectedValueException('Invalid OKX WebSocket payload.');
        }

        if (isset($payload['event'])) {
            if ($payload['event'] === 'error') {
                throw new \RuntimeException((string) ($payload['msg'] ?? 'OKX rejected the WebSocket request.'));
            }

            return null;
        }

        $instrument = $payload['arg']['instId'] ?? null;
        $data = $payload['data'][0] ?? null;
        $bid = is_array($data) ? ($data['bids'][0] ?? null) : null;
        $ask = is_array($data) ? ($data['asks'][0] ?? null) : null;

        if (
            ! is_string($instrument)
            || ! is_array($data)
            || ! is_array($bid)
            || ! isset($bid[0], $bid[1])
            || ! is_array($ask)
            || ! isset($ask[0], $ask[1])
        ) {
            throw new \UnexpectedValueException('Invalid OKX BBO message.');
        }

        return new Quote(
            symbol: strtoupper(str_replace('-', '', $instrument)),
            bidPrice: (string) $bid[0],
            bidQuantity: (string) $bid[1],
            askPrice: (string) $ask[0],
            askQuantity: (string) $ask[1],
            updateId: isset($data['seqId']) ? (int) $data['seqId'] : null,
        );
    }
}
