<?php

declare(strict_types=1);

namespace HyperfTest\Cases\Domain\MarketData\DTO;

use App\Domain\MarketData\DTO\Quote;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Quote::class)]
final class QuoteTest extends TestCase
{
    public function testConvertsQuoteToStandardArray(): void
    {
        $quote = new Quote(
            symbol: 'BTCUSDT',
            bidPrice: '108000.00',
            bidQuantity: '0.50',
            askPrice: '108001.00',
            askQuantity: '0.30',
            updateId: 123,
        );

        self::assertSame([
            'symbol' => 'BTCUSDT',
            'bid_price' => '108000.00',
            'bid_quantity' => '0.50',
            'ask_price' => '108001.00',
            'ask_quantity' => '0.30',
            'update_id' => 123,
        ], $quote->toArray());
    }
}
