<?php

declare(strict_types=1);

namespace App\Process\MarketData;

use App\Exchange\Bybit\WebSocket\BybitWebSocketService;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Process\AbstractProcess;
use Hyperf\Process\Annotation\Process;

#[Process(name: 'bybit-market-data')]
final class BybitMarketDataProcess extends AbstractProcess
{
    public int $nums = 1;

    public bool $enableCoroutine = true;

    public function handle(): void
    {
        $this->container->get(StdoutLoggerInterface::class)->info('Bybit market data process started');
        $this->container->get(BybitWebSocketService::class)->listen();
    }
}
