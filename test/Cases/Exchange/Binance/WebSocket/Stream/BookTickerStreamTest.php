<?php

declare(strict_types=1);

namespace HyperfTest\Cases\Exchange\Binance\WebSocket\Stream;

use App\Exchange\Binance\WebSocket\Stream\BookTickerStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(BookTickerStream::class)]
final class BookTickerStreamTest extends TestCase
{
    public function testBuildsCombinedBookTickerStream(): void
    {
        $path = (new BookTickerStream())->build(['BTCUSDT', 'ETHUSDT', 'SOLUSDT']);

        self::assertSame(
            '/stream?streams=btcusdt@bookTicker/ethusdt@bookTicker/solusdt@bookTicker',
            $path
        );
    }

    public function testRejectsEmptySymbolList(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new BookTickerStream())->build([]);
    }
}
