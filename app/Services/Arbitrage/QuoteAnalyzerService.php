<?php

declare(strict_types=1);

namespace App\Services\Arbitrage;

use App\Infrastructure\Redis\QuoteCache;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Coroutine\Coroutine;

final class QuoteAnalyzerService
{
    private const PRICE_SCALE = 8;

    private const DIVISION_SCALE = 10;

    private const PERCENT_SCALE = 6;

    /** @var list<string> */
    private readonly array $exchanges;

    private readonly int $maxQuoteAgeMs;

    private readonly string $minSpreadPercent;

    private readonly string $minLoggedSpreadPercent;

    private readonly int $sleepAfterOpportunitySeconds;

    public function __construct(
        private readonly QuoteCache $quoteCache,
        private readonly StdoutLoggerInterface $logger,
        ConfigInterface $config,
    ) {
        $this->exchanges = (array) array_values($config->get('arbitrage.exchanges'));
        $this->maxQuoteAgeMs = (int) $config->get('arbitrage.max_quote_age_ms');
        $this->minSpreadPercent = (string) $config->get('arbitrage.min_spread_percent');
        $this->minLoggedSpreadPercent = (string) $config->get('arbitrage.min_logged_spread_percent');
        $this->sleepAfterOpportunitySeconds = (int) $config->get('arbitrage.sleep_after_opportunity_seconds');
    }

    public function listen(string $symbol): void
    {
        $this->logger->info('Arbitrage analyzer started for {symbol}', ['symbol' => $symbol]);

        $this->quoteCache->subscribe($symbol, function () use ($symbol): void {
            try {
                $this->analyze($symbol);
            } catch (\Throwable $exception) {
                $this->logger->error('Quote analysis error for {symbol}: {error}', [
                    'symbol' => $symbol,
                    'error' => $exception->getMessage(),
                ]);
            }
        });
    }

    private function analyze(string $symbol): void
    {
        $quotes = $this->quoteCache->latest($symbol, $this->exchanges);

        if (count($quotes) < 2) {
            return;
        }

        $bestPrices = $this->findBestPrices($quotes);

        if ($bestPrices === null || $bestPrices['buy_exchange'] === $bestPrices['sell_exchange']) {
            return;
        }

        $this->compare(
            $symbol,
            $bestPrices['buy_exchange'],
            $bestPrices['buy_quote'],
            $bestPrices['sell_exchange'],
            $bestPrices['sell_quote']
        );
    }

    /**
     * @param array<string, array<string, mixed>> $quotes
     * @return null|array{
     *     buy_exchange: string,
     *     buy_quote: array<string, mixed>,
     *     sell_exchange: string,
     *     sell_quote: array<string, mixed>
     * }
     */
    private function findBestPrices(array $quotes): ?array
    {
        $buyExchange = null;
        $buyQuote = null;
        $sellExchange = null;
        $sellQuote = null;

        foreach ($quotes as $exchange => $quote) {
            if ($this->isQuoteStale($quote)) {
                continue;
            }

            $askPrice = $this->getAskPrice($quote);
            $bidPrice = $this->getBidPrice($quote);

            if (bccomp($askPrice, '0', self::PRICE_SCALE) > 0
                && ($buyQuote === null
                    || bccomp($askPrice, $this->getAskPrice($buyQuote), self::PRICE_SCALE) < 0)) {
                $buyExchange = $exchange;
                $buyQuote = $quote;
            }

            if (bccomp($bidPrice, '0', self::PRICE_SCALE) > 0
                && ($sellQuote === null
                    || bccomp($bidPrice, $this->getBidPrice($sellQuote), self::PRICE_SCALE) > 0)) {
                $sellExchange = $exchange;
                $sellQuote = $quote;
            }
        }

        if ($buyExchange === null || $buyQuote === null || $sellExchange === null || $sellQuote === null) {
            return null;
        }

        return [
            'buy_exchange' => $buyExchange,
            'buy_quote' => $buyQuote,
            'sell_exchange' => $sellExchange,
            'sell_quote' => $sellQuote,
        ];
    }

    /**
     * @param array<string, mixed> $buyQuote
     * @param array<string, mixed> $sellQuote
     */
    private function compare(
        string $symbol,
        string $buyExchange,
        array $buyQuote,
        string $sellExchange,
        array $sellQuote,
    ): void {
        // An immediate purchase uses the ask (lowest available selling price), while
        // an immediate sale uses the bid (highest available buying price).
        $buyPrice = $this->getAskPrice($buyQuote);
        $sellPrice = $this->getBidPrice($sellQuote);

        // A gross opportunity exists only when the selling price exceeds the buying price.
        if (! $this->hasProfitablePriceDifference($buyPrice, $sellPrice)) {
            return;
        }

        $priceDifference = $this->calculatePriceDifference($buyPrice, $sellPrice);

        // Gross spread (%) = ((sell price - buy price) / buy price) * 100.
        // Fees, slippage, and other costs have not yet been deducted from this result.
        $spreadPercent = $this->calculateSpreadPercent($priceDifference, $buyPrice);

        // Only spreads above the configured threshold are considered opportunities.
        if (! $this->spreadExceedsMinimum($spreadPercent)) {
            $this->logInsufficientSpreadWhenRelevant(
                $symbol,
                $buyExchange,
                $buyPrice,
                $sellExchange,
                $sellPrice,
                $spreadPercent
            );

            return;
        }

        $this->logOpportunity(
            $symbol,
            $buyExchange,
            $buyPrice,
            $sellExchange,
            $sellPrice,
            $spreadPercent
        );

        // Prevents repeated alerts for the same condition; only this symbol's process pauses.
        Coroutine::sleep($this->sleepAfterOpportunitySeconds);
    }

    /** @param array<string, mixed> $quote */
    private function getAskPrice(array $quote): string
    {
        return (string) ($quote['ask_price'] ?? '0');
    }

    /** @param array<string, mixed> $quote */
    private function getBidPrice(array $quote): string
    {
        return (string) ($quote['bid_price'] ?? '0');
    }

    private function hasProfitablePriceDifference(string $buyPrice, string $sellPrice): bool
    {
        $buyPriceIsValid = bccomp($buyPrice, '0', self::PRICE_SCALE) > 0;
        $sellPriceIsHigher = bccomp($sellPrice, $buyPrice, self::PRICE_SCALE) > 0;

        return $buyPriceIsValid && $sellPriceIsHigher;
    }

    private function calculatePriceDifference(string $buyPrice, string $sellPrice): string
    {
        return bcsub($sellPrice, $buyPrice, self::PRICE_SCALE);
    }

    private function calculateSpreadPercent(string $priceDifference, string $buyPrice): string
    {
        $spreadRatio = bcdiv($priceDifference, $buyPrice, self::DIVISION_SCALE);

        return bcmul($spreadRatio, '100', self::PERCENT_SCALE);
    }

    private function spreadExceedsMinimum(string $spreadPercent): bool
    {
        return bccomp($spreadPercent, $this->minSpreadPercent, self::PERCENT_SCALE) > 0;
    }

    private function logInsufficientSpreadWhenRelevant(
        string $symbol,
        string $buyExchange,
        string $buyPrice,
        string $sellExchange,
        string $sellPrice,
        string $spreadPercent,
    ): void {
        // Near opportunities are useful for monitoring. Smaller spreads are ignored
        // to avoid filling the logs with market variations that are not actionable.
        $reachesLoggingThreshold = bccomp(
            $spreadPercent,
            $this->minLoggedSpreadPercent,
            self::PERCENT_SCALE
        ) >= 0;
        $isBelowOpportunityThreshold = bccomp(
            $spreadPercent,
            $this->minSpreadPercent,
            self::PERCENT_SCALE
        ) < 0;

        // if (! $reachesLoggingThreshold || ! $isBelowOpportunityThreshold) {
        //     return;
        // }

        $this->logger->info(
            'Insufficient spread {symbol}: buy {buy_exchange} at {buy_price}, sell {sell_exchange} at {sell_price}, gross spread {spread}% (required above {minimum}%)',
            [
                'symbol' => $symbol,
                'buy_exchange' => $buyExchange,
                'buy_price' => $buyPrice,
                'sell_exchange' => $sellExchange,
                'sell_price' => $sellPrice,
                'spread' => $spreadPercent,
                'minimum' => $this->minSpreadPercent,
            ]
        );
    }

    private function logOpportunity(
        string $symbol,
        string $buyExchange,
        string $buyPrice,
        string $sellExchange,
        string $sellPrice,
        string $spreadPercent,
    ): void {
        $this->logger->info(
            'Arbitrage candidate {symbol}: buy {buy_exchange} at {buy_price}, sell {sell_exchange} at {sell_price}, gross spread {spread}%',
            [
                'symbol' => $symbol,
                'buy_exchange' => $buyExchange,
                'buy_price' => $buyPrice,
                'sell_exchange' => $sellExchange,
                'sell_price' => $sellPrice,
                'spread' => $spreadPercent,
            ]
        );
    }

    /** @param array<string, mixed> $quote */
    private function isQuoteStale(array $quote): bool
    {
        $receivedAt = (int) ($quote['received_at_ms'] ?? 0);

        return ((int) floor(microtime(true) * 1000)) - $receivedAt > $this->maxQuoteAgeMs;
    }
}
