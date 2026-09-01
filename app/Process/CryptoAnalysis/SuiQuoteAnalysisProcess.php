<?php

declare(strict_types=1);

namespace App\Process\CryptoAnalysis;

use App\Services\Arbitrage\QuoteAnalyzerService;
use Hyperf\Process\AbstractProcess;
use Hyperf\Process\Annotation\Process;

#[Process(name: 'sui-quote-analysis')]
final class SuiQuoteAnalysisProcess extends AbstractProcess
{
    public bool $enableCoroutine = true;

    public function handle(): void
    {
        $this->container->get(QuoteAnalyzerService::class)->listen('SUIUSDT');
    }
}
