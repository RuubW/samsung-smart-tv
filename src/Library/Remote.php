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
	private function validateKey($key): bool
    {
        if (substr($key, 0, 4) == 'KEY_') {
            $key = substr($key, 4);
        }

		return (empty($this->validKeys) || in_array($key, $this->validKeys));
	}

	/**
	 * Create the JSON message to send in the websocket request.
     *
     * @param $sKey
     *
     * @return string
	 */
	private function getKeypressMessage($sKey): string
	{
		$aMessage = [
			'method' => 'ms.remote.control',
			'params' => [
				'Cmd' => 'Click',
				'DataOfCmd' => $sKey,
				'Option' => false,
				'TypeOfRemote' => 'SendRemoteKey'
			]
		];

		return json_encode($aMessage, JSON_PRETTY_PRINT);
	}

	/**
	 * Add a keypress to the queue.
     *
	 * @param string $sKey
	 * @param float $fDelay
	 */
	public function queueKey($sKey, $fDelay = 1.0): void
    {
        if (!$this->validateKey($sKey)) {
            throw new UnexpectedValueException("Invalid key: {$sKey}");
        }

		$this->queue[] = [
		    'key' => $sKey,
            'delay' => $fDelay
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
	 * @param string $sKey
	 * @param float $fDelay
	 */
	public function sendKey($sKey, $fDelay = 1.0): void
	{
		$this->clearQueue();
		$this->queueKey($sKey, $fDelay);
		$this->sendQueue();
	}

	/**
	 * Pop the top key and send it, then schedule the next keypress.
     *
	 * @param WebSocket $conn
	 * @param LoopInterface $loop
	 */
	private function sendQueueKeys(WebSocket $conn, LoopInterface $loop): void
	{
		$aKeyDef = array_pop($this->queue);
		if (!is_null($aKeyDef)) {
			$sKey = $aKeyDef['key'];
			$jsonMessage = $this->getKeypressMessage($sKey);
			$this->logger->debug("Sending {$sKey}...");
			$conn->send($jsonMessage);

			$loop->addTimer($aKeyDef['delay'], function() use ($conn, $loop) {
				$this->sendQueueKeys($conn, $loop);
			});
		} else {
			// all keys sent, so disconnect socket
			$this->logger->debug('Closing websocket');
			$conn->close();
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
		$subProtocols = [];
		$headers = [];
		$connector($remoteUrl, $subProtocols, $headers)->then(function(WebSocket $conn) use ($loop, $cacheItem) {
			$conn->on('message', function(MessageInterface $msg) use ($conn, $loop, $cacheItem) {
				$oMsg = json_decode($msg);
				if ($oMsg->event == 'ms.channel.connect') {
                    if (property_exists($oMsg->data, 'token')) {
                        $cacheItem->set($oMsg->data->token);
                        $this->cache->save($cacheItem);
                    }

					$this->logger->debug('Connected');
					$this->sendQueueKeys($conn, $loop);
				} else {
					$this->logger->error("Unknown message: {$msg}");
					throw new RemoteException("Unknown message received: {$msg}");
				}
			});
		

		}, function($e) {
			$this->logger->error("Could not connect: {$e->getMessage()}");
			throw new RemoteException("Could not connect: {$e->getMessage()}", NULL, $e);
		});

		$loop->run();
	}
}
