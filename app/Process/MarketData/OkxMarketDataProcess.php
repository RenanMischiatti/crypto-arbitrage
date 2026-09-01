<?php

declare(strict_types=1);

namespace App\Process\MarketData;

use App\Exchange\Okx\WebSocket\OkxWebSocketService;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Process\AbstractProcess;
use Hyperf\Process\Annotation\Process;

#[Process(name: 'okx-market-data')]
final class OkxMarketDataProcess extends AbstractProcess
{
    public int $nums = 1;

    public bool $enableCoroutine = true;

    public function handle(): void
    {
        $this->container->get(StdoutLoggerInterface::class)->info('OKX market data process started');
        $this->container->get(OkxWebSocketService::class)->listen();
    }
}
