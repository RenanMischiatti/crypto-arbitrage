<?php

declare(strict_types=1);

namespace App\Exchange\Bybit\WebSocket;

use App\Exchange\Bybit\BybitMessageHandler;
use App\Exchange\Bybit\WebSocket\Stream\OrderBookStream;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Coroutine\Coroutine;
use Hyperf\Process\ProcessManager;
use Swoole\Coroutine\Http\Client;
use Swoole\WebSocket\Frame;

final class BybitWebSocketService
{
    private readonly string $host;

    private readonly int $port;

    private readonly string $path;

    private readonly bool $ssl;

    private readonly float $pingIntervalSeconds;

    private readonly float $reconnectDelaySeconds;

    /** @var list<string> */
    private readonly array $symbols;

    public function __construct(
        private readonly StdoutLoggerInterface $logger,
        private readonly BybitMessageHandler $messageHandler,
        private readonly OrderBookStream $stream,
        ConfigInterface $config,
    ) {
        $this->host = (string) $config->get('exchanges.bybit.websocket.host');
        $this->port = (int) $config->get('exchanges.bybit.websocket.port');
        $this->path = (string) $config->get('exchanges.bybit.websocket.path');
        $this->ssl = (bool) $config->get('exchanges.bybit.websocket.ssl');
        $this->pingIntervalSeconds = (float) $config->get('exchanges.bybit.websocket.ping_interval_seconds');
        $this->reconnectDelaySeconds = (float) $config->get('exchanges.bybit.websocket.reconnect_delay_seconds');
        $this->symbols = (array) array_values($config->get('exchanges.bybit.symbols', []));
    }

    public function listen(): void
    {
        while (ProcessManager::isRunning()) {
            try {
                $this->connectAndConsumeMessages();
            } catch (\Throwable $exception) {
                $this->logger->error('Bybit WebSocket error: ' . $exception->getMessage());

                Coroutine::sleep($this->reconnectDelaySeconds);
            }
        }
    }

    private function connectAndConsumeMessages(): void
    {
        $client = $this->connect();
        $lastPingAt = microtime(true);

        try {
            while (ProcessManager::isRunning()) {
                $this->handleFrame($client, $client->recv());

                if (microtime(true) - $lastPingAt >= $this->pingIntervalSeconds) {
                    $client->push('{"op":"ping"}');
                    $lastPingAt = microtime(true);
                }
            }
        } finally {
            $client->close();
        }
    }

    private function connect(): Client
    {
        $client = new Client($this->host, $this->port, $this->ssl);
        $client->setHeaders(['Host' => $this->host]);

        $this->logger->info('Connecting to Bybit WebSocket at {host} for {symbols}', [
            'host' => $this->host,
            'symbols' => implode(', ', $this->symbols),
        ]);

        if (! $client->upgrade($this->path)) {
            $error = sprintf(
                'WebSocket handshake failed (status %d, error %d).',
                $client->statusCode,
                $client->errCode
            );
            $client->close();

            throw new \RuntimeException($error);
        }

        if (! $client->push($this->stream->subscribe($this->symbols))) {
            $client->close();

            throw new \RuntimeException('Failed to subscribe to Bybit orderbook topics.');
        }

        return $client;
    }

    private function handleFrame(Client $client, Frame|bool|string $frame): void
    {
        if (! $frame instanceof Frame) {
            throw new \RuntimeException(sprintf(
                'Bybit WebSocket disconnected (error %d).',
                $client->errCode
            ));
        }

        if ($frame->opcode === SWOOLE_WEBSOCKET_OPCODE_PING) {
            $client->push($frame->data, SWOOLE_WEBSOCKET_OPCODE_PONG);

            return;
        }

        if ($frame->opcode === SWOOLE_WEBSOCKET_OPCODE_CLOSE) {
            throw new \RuntimeException('Bybit closed the WebSocket connection.');
        }

        if ($frame->opcode !== SWOOLE_WEBSOCKET_OPCODE_TEXT || ! is_string($frame->data)) {
            return;
        }

        $quote = $this->messageHandler->handle($frame->data);

        if ($quote !== null) {
            $this->logger->info('Bybit quote: {quote}', [
                'quote' => json_encode($quote->toArray(), JSON_THROW_ON_ERROR),
            ]);
        }
    }
}
