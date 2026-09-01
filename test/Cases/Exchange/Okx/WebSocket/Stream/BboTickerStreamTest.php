<?php

declare(strict_types=1);

namespace HyperfTest\Cases\Exchange\Okx\WebSocket\Stream;

use App\Exchange\Okx\WebSocket\Stream\BboTickerStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(BboTickerStream::class)]
final class BboTickerStreamTest extends TestCase
{
    public function testBuildsSubscriptionForConfiguredSymbols(): void
    {
        $subscription = (new BboTickerStream())->subscribe(['BTCUSDT', 'ETHUSDT', 'SOLUSDT']);

        self::assertSame([
            'op' => 'subscribe',
            'args' => [
                ['channel' => 'bbo-tbt', 'instId' => 'BTC-USDT'],
                ['channel' => 'bbo-tbt', 'instId' => 'ETH-USDT'],
                ['channel' => 'bbo-tbt', 'instId' => 'SOL-USDT'],
            ],
        ], json_decode($subscription, true, 512, JSON_THROW_ON_ERROR));
    }
}
