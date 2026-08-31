<?php

declare(strict_types=1);

namespace HyperfTest\Cases\Exchange\Binance;

use App\Domain\MarketData\DTO\Quote;
use App\Exchange\Binance\BinanceMessageHandler;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(BinanceMessageHandler::class)]
final class BinanceMessageHandlerTest extends TestCase
{
    public function testConvertsBinanceMessageToQuote(): void
    {
        $message = json_encode([
            'stream' => 'btcusdt@bookTicker',
            'data' => [
                'u' => 123,
                's' => 'BTCUSDT',
                'b' => '108000.00',
                'B' => '0.50',
                'a' => '108001.00',
                'A' => '0.30',
            ],
        ], JSON_THROW_ON_ERROR);

        $quote = (new BinanceMessageHandler())->handle($message);

        self::assertInstanceOf(Quote::class, $quote);
        self::assertSame('BTCUSDT', $quote->symbol);
        self::assertSame('108000.00', $quote->bidPrice);
        self::assertSame('108001.00', $quote->askPrice);
    }
}
