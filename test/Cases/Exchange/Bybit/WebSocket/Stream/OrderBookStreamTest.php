<?php

declare(strict_types=1);

namespace HyperfTest\Cases\Exchange\Bybit\WebSocket\Stream;

use App\Exchange\Bybit\WebSocket\Stream\OrderBookStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(OrderBookStream::class)]
final class OrderBookStreamTest extends TestCase
{
    public function testBuildsSubscriptionForConfiguredSymbols(): void
    {
        $subscription = (new OrderBookStream())->subscribe(['BTCUSDT', 'ETHUSDT', 'SOLUSDT']);

        self::assertSame([
            'op' => 'subscribe',
            'args' => [
                'orderbook.1.BTCUSDT',
                'orderbook.1.ETHUSDT',
                'orderbook.1.SOLUSDT',
            ],
        ], json_decode($subscription, true, 512, JSON_THROW_ON_ERROR));
    }
}
