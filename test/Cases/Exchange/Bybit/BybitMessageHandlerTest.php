<?php

declare(strict_types=1);

namespace HyperfTest\Cases\Exchange\Bybit;

use App\Exchange\Bybit\BybitMessageHandler;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(BybitMessageHandler::class)]
final class BybitMessageHandlerTest extends TestCase
{
    public function testConvertsLevelOneOrderbookToQuote(): void
    {
        $message = json_encode([
            'topic' => 'orderbook.1.BTCUSDT',
            'type' => 'snapshot',
            'data' => [
                's' => 'BTCUSDT',
                'b' => [['108000.00', '0.50']],
                'a' => [['108001.00', '0.30']],
                'u' => 123,
            ],
        ], JSON_THROW_ON_ERROR);

        $quote = (new BybitMessageHandler())->handle($message);

        self::assertNotNull($quote);
        self::assertSame('BTCUSDT', $quote->symbol);
        self::assertSame('108000.00', $quote->bidPrice);
        self::assertSame('108001.00', $quote->askPrice);
    }

    public function testIgnoresSubscriptionConfirmation(): void
    {
        $message = '{"success":true,"ret_msg":"subscribe","op":"subscribe"}';

        self::assertNull((new BybitMessageHandler())->handle($message));
    }
}
