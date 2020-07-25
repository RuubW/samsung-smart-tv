<?php

namespace App\Library;

use App\Exception\RemoteException;
use Psr\Log\LoggerInterface;
use Ratchet\Client\Connector;
use Ratchet\Client\WebSocket;
use Ratchet\RFC6455\Messaging\MessageInterface;
use React\EventLoop\Factory as ReactFactory;
use React\EventLoop\LoopInterface;
use Symfony\Component\Cache\Adapter\AdapterInterface;

/**
 * Class RemoteClient.
 * Based on https://github.com/benreidnet/samsungtv.
 *
 * @package App\Library
 */
class RemoteClient
{
    /**
     * @var AdapterInterface
     */
    private $cache;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var string
     */
    private $host;

    /**
     * @var string
     */
    private $protocol;

    /**
     * @var int
     */
    private $port;

    /**
     * @var string
     */
    private $appName;

    /**
     * @var string
     */
    private $environment;

    /**
     * @var array
     */
    private $queue = [];

    // Remote connection URL.
    private const REMOTE_URL = '%s://%s:%d/api/v2/channels/samsung.remote.control?name=%s%s';

    // Token query string part of the remote connection URL.
    private const REMOTE_URL_TOKEN_QUERY = '&token=%s';

    // Security context settings for the websocket connector in dev.
    private const SECURE_CONTEXT_DEV = [
        'verify_peer' => false,
        'verify_peer_name' => false,
        'allow_self_signed' => true
    ];

    /**
     * RemoteClient constructor.
     *
     * @param AdapterInterface $cache
     * @param LoggerInterface $logger
     * @param string $host
     * @param string $protocol
     * @param int $port
     * @param string $appName
     * @param string $environment
     */
    public function __construct(
        AdapterInterface $cache,
        LoggerInterface $logger,
        string $host,
        string $protocol,
        int $port,
        string $appName,
        string $environment
    ) {
        $this->cache = $cache;
        $this->logger = $logger;
        $this->host = $host;
        $this->protocol = $protocol;
        $this->port = $port;
        $this->appName = $appName;
        $this->environment = $environment;
    }

    /**
     * Get the remote host.
     *
     * @return string
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * Create the JSON message to send in the websocket request.
     *
     * @param string $key
     *
     * @return string
     */
    private function getKeypressMessage(string $key): string
    {
        $message = [
            'method' => 'ms.remote.control',
            'params' => [
                'Cmd' => 'Click',
                'DataOfCmd' => "KEY_{$key}",
                'Option' => false,
                'TypeOfRemote' => 'SendRemoteKey'
            ]
        ];

        return json_encode($message, JSON_PRETTY_PRINT);
    }

    /**
     * Add a keypress to the queue.
     *
     * @param string $key
     * @param float $delay
     */
    public function queueKey(string $key, float $delay = 1.0): void
    {
        $this->queue[] = [
            'key' => strtoupper($key),
            'delay' => $delay
        ];
    }

    /**
     * Clear any outstanding items in the key queue.
     */
    public function clearQueue(): void
    {
        $this->queue = [];
    }

    /**
     * Wrapper function to send an individual key to the TV (clears the queue first).
     *
     * @param string $key
     * @param float $delay
     */
    public function sendKey(string $key, float $delay = 1.0): void
    {
        $this->clearQueue();
        $this->queueKey($key, $delay);
        $this->sendQueue();
    }

    /**
     * Wrapper function to send an array of keys to the TV (clears the queue first).
     *
     * @param array $keys
     * @param float $delay
     */
    public function sendKeys(array $keys, float $delay = 1.0): void
    {
        $this->clearQueue();
        foreach ($keys as $key) {
            $this->queueKey($key, $delay);
        }
        $this->sendQueue();
    }

    /**
     * Pop the top key and send it, then schedule the next keypress.
     *
     * @param WebSocket $connection
     * @param LoopInterface $loop
     */
    private function sendQueueKeys(WebSocket $connection, LoopInterface $loop): void
    {
        $queueItem = array_pop($this->queue);
        if (!is_null($queueItem)) {
            $key = $queueItem['key'];

            $this->logger->debug("Sending {$key}...");

            // Prepare and send the message.
            $jsonMessage = $this->getKeypressMessage($key);
            $connection->send($jsonMessage);

            // Send the next queue key.
            $loop->addTimer($queueItem['delay'], function () use ($connection, $loop) {
                $this->sendQueueKeys($connection, $loop);
            });
        } else {
            $this->logger->debug('Closing websocket');

            // All the keys were sent, disconnect the socket.
            $connection->close();
        }
    }

    /**
     * Send queued keypresses to TV.
     */
    public function sendQueue(): void
    {
        if (count($this->queue) == 0) {
            $this->logger->warning('No keys to send');

            return;
        }

        // Retrieve the TV access token.
        $cacheItem = $this->cache->getItem('remote_token');
        $tokenQuery = '';
        if ($cacheItem->isHit()) {
            // Prepare the access token query string.
            $tokenQuery = sprintf(
                self::REMOTE_URL_TOKEN_QUERY,
                $cacheItem->get()
            );
        }

        // Prepare the remote TV URL.
        $remoteUrl = sprintf(
            self::REMOTE_URL,
            $this->protocol,
            $this->host,
            $this->port,
            utf8_encode(base64_encode($this->appName)),
            $tokenQuery
        );

        $this->logger->debug("Connecting to {$remoteUrl}");

        $loop = ReactFactory::create();
        $connector = new Connector(
            $loop,
            null,
            ($this->environment === 'dev') ? self::SECURE_CONTEXT_DEV : []
        );

        $connector($remoteUrl)->then(
            function (WebSocket $connection) use ($loop, $cacheItem) {
                $connection->on(
                    'message',
                    function (MessageInterface $messageJSON) use ($connection, $loop, $cacheItem) {
                        $message = json_decode($messageJSON);
                        // Handle the handshake response.
                        if ($message->event == 'ms.channel.connect') {
                            // Save the TV access token.
                            if (property_exists($message->data, 'token')) {
                                $cacheItem->set($message->data->token);
                                $this->cache->save($cacheItem);
                            }

                            $this->logger->debug('Connected');

                            // Send the queue keys.
                            $this->sendQueueKeys($connection, $loop);
                        } else {
                            $this->logger->error("Unknown message: {$messageJSON}");

                            throw new RemoteException("Unknown message received: {$messageJSON}");
                        }
                    }
                );
            },
            function ($e) {
                $this->logger->error("Could not connect: {$e->getMessage()}");

                throw new RemoteException("Could not connect: {$e->getMessage()}", null, $e);
            }
        );

        $loop->run();
    }
}
