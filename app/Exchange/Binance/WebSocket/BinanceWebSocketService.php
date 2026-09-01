<?php

declare(strict_types=1);

namespace App\Exchange\Binance\WebSocket;

use App\Infrastructure\Redis\QuoteCache;
use App\Exchange\Binance\BinanceMessageHandler;
use App\Exchange\Binance\WebSocket\Stream\BookTickerStream;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Coroutine\Coroutine;
use Hyperf\Process\ProcessManager;
use Swoole\Coroutine\Http\Client;
use Swoole\WebSocket\Frame;

final class BinanceWebSocketService
{
    private readonly string $host;

    private readonly int $port;

    private readonly bool $ssl;

    private readonly float $reconnectDelaySeconds;

    /** @var list<string> */
    private readonly array $symbols;

    public function __construct(
        private readonly StdoutLoggerInterface $logger,
        private readonly BinanceMessageHandler $messageHandler,
        private readonly BookTickerStream $stream,
        private readonly QuoteCache $quoteCache,
        ConfigInterface $config,
    ) {
        $this->host                  = (string) $config->get('exchanges.binance.websocket.host');
        $this->port                  = (int) $config->get('exchanges.binance.websocket.port');
        $this->ssl                   = (bool) $config->get('exchanges.binance.websocket.ssl');
        $this->symbols               = (array) array_values($config->get('exchanges.binance.symbols'));
        $this->reconnectDelaySeconds = (float) $config->get('exchanges.binance.websocket.reconnect_delay_seconds');
    }

    public function listen(): void
    {
        while (ProcessManager::isRunning()) {
            try {
                $this->connectAndConsumeMessages();
            } catch (\Throwable $exception) {
                $this->logger->error('Binance WebSocket error: ' . $exception->getMessage());

                Coroutine::sleep($this->reconnectDelaySeconds);
            }
        }
    }

    private function connectAndConsumeMessages(): void
    {
        $client = $this->connect();
        try {
            while (ProcessManager::isRunning()) {
                $this->handleFrame($client, $client->recv());
            }
        } finally {
            $client->close();
        }
    }

    private function connect(): Client
    {
        $client = new Client($this->host, $this->port, $this->ssl);
        $client->setHeaders(['Host' => $this->host]);

        $this->logger->info('Connecting to Binance WebSocket at {host} for {symbols}', [
            'host' => $this->host,
            'symbols' => implode(', ', $this->symbols),
        ]);

        if ($client->upgrade($this->stream->build($this->symbols))) {
            return $client;
        }

        $error = sprintf(
            'WebSocket handshake failed (status %d, error %d).',
            $client->statusCode,
            $client->errCode
        );
        $client->close();

        throw new \RuntimeException($error);
    }

    private function handleFrame(Client $client, Frame|bool|string $frame): void
    {
        if (! $frame instanceof Frame) {
            throw new \RuntimeException(sprintf(
                'Binance WebSocket disconnected (error %d).',
                $client->errCode
            ));
        }

        if ($frame->opcode === SWOOLE_WEBSOCKET_OPCODE_PING) {
            $client->push($frame->data, SWOOLE_WEBSOCKET_OPCODE_PONG);

            return;
        }

        if ($frame->opcode === SWOOLE_WEBSOCKET_OPCODE_CLOSE) {
            throw new \RuntimeException('Binance closed the WebSocket connection.');
        }

        if ($frame->opcode === SWOOLE_WEBSOCKET_OPCODE_TEXT && is_string($frame->data)) {
            $quote = $this->messageHandler->handle($frame->data);
            $this->quoteCache->save('binance', $quote);
        }
    }
}
