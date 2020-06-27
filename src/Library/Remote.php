<?php

namespace App\Library;

use App\Exception\RemoteException;
use Ratchet\RFC6455\Messaging\MessageInterface;
use React\EventLoop\Factory as ReactFactory;
use React\EventLoop\LoopInterface;
use Ratchet\Client\Connector;
use Psr\Log\LoggerInterface;
use Ratchet\Client\WebSocket;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use UnexpectedValueException;

/**
 * Class Remote.
 * Based on https://github.com/benreidnet/samsungtv
 *
 * @package App\Library
 */
class Remote
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
     *
     * wss for secure, ws for insecure
     */
	private $protocol = 'wss';

	/**
     * @var integer
     *
     * 8002 for secure, 8001 for insecure
	 */
    private $port = 8002;

	/**
	 * @var string
	 */
	private $appName = 'PHP Remote';

    /**
     * @var array
     */
    private $validKeys;

	/**
	 * @var array
	 */
	private $queue = [];

	/**
	 * Remote constructor.
     *
     * @param AdapterInterface $cache
     * @param LoggerInterface $logger
     * @param string $host
     * @param array $validKeys
	 */
	public function __construct(
        AdapterInterface $cache,
        LoggerInterface $logger,
	    string $host,
        array $validKeys
    ) {
        $this->cache = $cache;
        $this->logger = $logger;
        $this->host = $host;
        $this->validKeys = $validKeys;
	}

	/**
	 * Get the remote host
     *
	 * @return string
	 */
	public function getHost(): string
	{
		return $this->host;
	}

	/**
	 * Validate a key against the list of valid keys.
     *
	 * @param string $key
     *
	 * @return bool
	 */
	private function validateKey(string $key): bool
    {
        if (substr($key, 0, 4) == 'KEY_') {
            $key = substr($key, 4);
        }

		return (empty($this->validKeys) || in_array($key, $this->validKeys));
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
				'DataOfCmd' => $key,
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
        if (!$this->validateKey($key)) {
            throw new UnexpectedValueException("Invalid key: {$key}");
        }

		$this->queue[] = [
		    'key' => $key,
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
			$jsonMessage = $this->getKeypressMessage($key);
			$this->logger->debug("Sending {$key}...");
            $connection->send($jsonMessage);

			$loop->addTimer($queueItem['delay'], function() use ($connection, $loop) {
				$this->sendQueueKeys($connection, $loop);
			});
		} else {
			// all keys sent, so disconnect socket
			$this->logger->debug('Closing websocket');
            $connection->close();
		}
	}

	/**
	 * Send queued keypresses to TV.
	 */
	public function sendQueue(): void
	{
		if (count($this->queue) == 0) {
			$this->logger->warn('No keys to send');
			return;
		}

		$cacheItem = $this->cache->getItem('remote_token');
        $tokenQuery = '';
		if ($cacheItem->isHit()) {
            $tokenQuery = "&token={$cacheItem->get()}";
        }

		$remoteUrl = sprintf(
		    '%s://%s:%d/api/v2/channels/samsung.remote.control?name=%s%s',
            $this->protocol,
            $this->host,
            $this->port,
            utf8_encode(base64_encode($this->appName)),
            $tokenQuery
        );

		$this->logger->debug("Connecting to {$remoteUrl}");

		$loop = ReactFactory::create();
		$connector = new Connector($loop, null, [
		    'verify_peer' => false,
            'verify_peer_name' => false
        ]);

		$connector($remoteUrl)->then(
            function(WebSocket $connection) use ($loop, $cacheItem) {
                $connection->on(
                    'message',
                    function(MessageInterface $messageJSON) use ($connection, $loop, $cacheItem) {
                        $message = json_decode($messageJSON);
                        if ($message->event == 'ms.channel.connect') {
                            if (property_exists($message->data, 'token')) {
                                $cacheItem->set($message->data->token);
                                $this->cache->save($cacheItem);
                            }

                            $this->logger->debug('Connected');
                            $this->sendQueueKeys($connection, $loop);
                        } else {
                            $this->logger->error("Unknown message: {$messageJSON}");
                            throw new RemoteException("Unknown message received: {$messageJSON}");
                        }
                    }
                );
            },
            function($e) {
                $this->logger->error("Could not connect: {$e->getMessage()}");
                throw new RemoteException("Could not connect: {$e->getMessage()}", NULL, $e);
            }
        );

		$loop->run();
	}
}
