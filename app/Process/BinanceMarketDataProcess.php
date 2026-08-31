<?php

declare(strict_types=1);

namespace App\Process;

use App\Exchange\Binance\WebSocket\BinanceWebSocketService;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Process\AbstractProcess;
use Hyperf\Process\Annotation\Process;

#[Process(name: 'binance-market-data')]
final class BinanceMarketDataProcess extends AbstractProcess
{
    public int $nums = 1;

    public bool $enableCoroutine = true;

    public function handle(): void
    {
        $logger = $this->container->get(
            StdoutLoggerInterface::class
        );

        $logger->info('Binance market data process started');

        $this->container->get(BinanceWebSocketService::class)->listen();
    }
}
