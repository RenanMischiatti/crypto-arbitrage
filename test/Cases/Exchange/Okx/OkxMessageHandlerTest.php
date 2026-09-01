<?php

declare(strict_types=1);

namespace HyperfTest\Cases\Exchange\Okx;

use App\Exchange\Okx\OkxMessageHandler;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(OkxMessageHandler::class)]
final class OkxMessageHandlerTest extends TestCase
{
    public function testConvertsBboMessageToQuote(): void
    {
        $message = json_encode([
            'arg' => [
                'channel' => 'bbo-tbt',
                'instId' => 'BTC-USDT',
            ],
            'data' => [[
                'asks' => [['108001.00', '0.30', '0', '1']],
                'bids' => [['108000.00', '0.50', '0', '1']],
                'seqId' => 123,
                'ts' => '1756742400000',
            ]],
        ], JSON_THROW_ON_ERROR);

        $quote = (new OkxMessageHandler())->handle($message);

        self::assertNotNull($quote);
        self::assertSame('BTCUSDT', $quote->symbol);
        self::assertSame('108000.00', $quote->bidPrice);
        self::assertSame('0.50', $quote->bidQuantity);
        self::assertSame('108001.00', $quote->askPrice);
        self::assertSame('0.30', $quote->askQuantity);
        self::assertSame(123, $quote->updateId);
    }

    public function testIgnoresSubscriptionConfirmation(): void
    {
        $message = '{"event":"subscribe","arg":{"channel":"bbo-tbt","instId":"BTC-USDT"}}';

        self::assertNull((new OkxMessageHandler())->handle($message));
    }
}
