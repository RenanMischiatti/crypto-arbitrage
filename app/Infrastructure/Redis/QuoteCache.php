<?php

declare(strict_types=1);

namespace App\Infrastructure\Redis;

use App\Domain\MarketData\DTO\Quote;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Redis\Redis;

final class QuoteCache
{
    private readonly int $ttlSeconds;

    public function __construct(
        private readonly Redis $redis,
        ConfigInterface $config,
    ) {
        $this->ttlSeconds = (int) $config->get('arbitrage.quote_ttl_seconds');
    }

    public function save(string $exchange, Quote $quote): void
    {
        $data = [
            'exchange' => $exchange,
            ...$quote->toArray(),
            'received_at_ms' => (int) floor(microtime(true) * 1000),
        ];

        $key = $this->key($exchange, $quote->symbol);
        $channel = $this->channel($quote->symbol);
        $value = json_encode($data, JSON_THROW_ON_ERROR);
        $ttlSeconds = $this->ttlSeconds;

        $this->redis->pipeline(static function (mixed $redis) use ($key, $channel, $value, $exchange, $ttlSeconds): void {
            $redis->setex($key, $ttlSeconds, $value);
            $redis->publish($channel, $exchange);
        });
    }

    /**
     * @param list<string> $exchanges
     * @return array<string, array<string, mixed>>
     */
    public function latest(string $symbol, array $exchanges): array
    {
        $keys = array_map(
            fn (string $exchange): string => $this->key($exchange, $symbol),
            $exchanges
        );
        $values = $this->redis->mget($keys);
        $quotes = [];

        if (! is_array($values)) {
            return [];
        }

        foreach ($exchanges as $index => $exchange) {
            $value = $values[$index] ?? false;

            if (! is_string($value)) {
                continue;
            }

            $quote = json_decode($value, true, 512, JSON_THROW_ON_ERROR);

            if (is_array($quote)) {
                $quotes[$exchange] = $quote;
            }
        }

        return $quotes;
    }

    public function subscribe(string $symbol, callable $listener): void
    {
        $this->redis->subscribe(
            [$this->channel($symbol)],
            static fn (mixed $_redis, string $_channel, string $exchange) => $listener($exchange)
        );
    }

    private function key(string $exchange, string $symbol): string
    {
        return sprintf('quote:latest:%s:%s', strtolower($exchange), strtoupper($symbol));
    }

    private function channel(string $symbol): string
    {
        return 'quote:updated:' . strtoupper($symbol);
    }
}
