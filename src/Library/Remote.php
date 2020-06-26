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
	private $queue = [];

	/**
	 * @var array
     *
	 * This list is taken from https://github.com/Bntdumas/SamsungIPRemote/blob/master/samsungKeyCodes.txt
	 */
	private $aValidKeys = array(
        '0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '11', '12', '4_3', '16_9', '3SPEED', 'AD', 'ADDDEL',
        'ALT_MHP', 'ANGLE', 'ANTENA', 'ANYNET', 'ANYVIEW', 'APP_LIST', 'ASPECT', 'AUTO_ARC_ANTENNA_AIR',
        'AUTO_ARC_ANTENNA_CABLE', 'AUTO_ARC_ANTENNA_SATELLITE', 'AUTO_ARC_ANYNET_AUTO_START',
        'AUTO_ARC_ANYNET_MODE_OK', 'AUTO_ARC_AUTOCOLOR_FAIL', 'AUTO_ARC_AUTOCOLOR_SUCCESS', 'AUTO_ARC_C_FORCE_AGING',
        'AUTO_ARC_CAPTION_ENG', 'AUTO_ARC_CAPTION_KOR', 'AUTO_ARC_CAPTION_OFF', 'AUTO_ARC_CAPTION_ON',
        'AUTO_ARC_C_FORCE_AGING', 'AUTO_ARC_JACK_IDENT', 'AUTO_ARC_LNA_OFF', 'AUTO_ARC_LNA_ON',
        'AUTO_ARC_PIP_CH_CHANGE', 'AUTO_ARC_PIP_DOUBLE', 'AUTO_ARC_PIP_LARGE', 'AUTO_ARC_PIP_LEFT_BOTTOM',
        'AUTO_ARC_PIP_LEFT_TOP', 'AUTO_ARC_PIP_RIGHT_BOTTOM', 'AUTO_ARC_PIP_RIGHT_TOP', 'AUTO_ARC_PIP_SMALL',
        'AUTO_ARC_PIP_SOURCE_CHANGE', 'AUTO_ARC_PIP_WIDE', 'AUTO_ARC_RESET', 'AUTO_ARC_USBJACK_INSPECT', 'AUTO_FORMAT',
        'AUTO_PROGRAM', 'AV1', 'AV2', 'AV3', 'BACK_MHP', 'BLUE', 'BOOKMARK', 'CALLER_ID', 'CAPTION', 'CATV_MODE',
        'CHDOWN', 'CH_LIST', 'CHUP', 'CLEAR', 'CLOCK_DISPLAY', 'COMPONENT1', 'COMPONENT2', 'CONTENTS', 'CONVERGENCE',
        'CONVERT_AUDIO_MAINSUB', 'CUSTOM', 'CYAN', 'DEVICE_CONNECT', 'DISC_MENU', 'DMA', 'DNET', 'DNIe', 'DNSe',
        'DOOR', 'DOWN', 'DSS_MODE', 'DTV', 'DTV_LINK', 'DTV_SIGNAL', 'DVD_MODE', 'DVI', 'DVR', 'DVR_MENU', 'DYNAMIC',
        'ENTER', 'ENTERTAINMENT', 'ESAVING', 'EXT1', 'EXT10', 'EXT11', 'EXT12', 'EXT13', 'EXT14', 'EXT15', 'EXT16',
        'EXT17', 'EXT18', 'EXT19', 'EXT2', 'EXT20', 'EXT21', 'EXT22', 'EXT23', 'EXT24', 'EXT25', 'EXT26', 'EXT27',
        'EXT28', 'EXT29', 'EXT3', 'EXT30', 'EXT31', 'EXT32', 'EXT33', 'EXT34', 'EXT35', 'EXT36', 'EXT37', 'EXT38',
        'EXT39', 'EXT4', 'EXT40', 'EXT41', 'EXT5', 'EXT6', 'EXT7', 'EXT8', 'EXT9', 'FACTORY', 'FAVCH', 'FF', 'FF_',
        'FM_RADIO', 'GAME', 'GREEN', 'GUIDE', 'HDMI', 'HDMI1', 'HDMI2', 'HDMI3', 'HDMI4', 'HELP', 'HOME', 'ID_INPUT',
        'ID_SETUP', 'INFO', 'INSTANT_REPLAY', 'LEFT', 'LINK', 'LIVE', 'MAGIC_BRIGHT', 'MAGIC_CHANNEL', 'MDC', 'MENU',
        'MIC', 'MORE', 'MOVIE1', 'MS', 'MTS', 'MUTE', 'NINE_SEPERATE', 'OPEN', 'PANNEL_CHDOWN', 'PANNEL_CHUP',
        'PANNEL_ENTER', 'PANNEL_MENU', 'PANNEL_POWER', 'PANNEL_SOURCE', 'PANNEL_VOLDOW', 'PANNEL_VOLUP', 'PANORAMA',
        'PAUSE', 'PCMODE', 'PERPECT_FOCUS', 'PICTURE_SIZE', 'PIP_CHDOWN', 'PIP_CHUP', 'PIP_ONOFF', 'PIP_SCAN',
        'PIP_SIZE', 'PIP_SWAP', 'PLAY', 'PLUS100', 'PMODE', 'POWER', 'POWEROFF', 'POWERON', 'PRECH', 'PRINT',
        'PROGRAM', 'QUICK_REPLAY', 'REC', 'RED', 'REPEAT', 'RESERVED1', 'RETURN', 'REWIND', 'REWIND_', 'RIGHT',
        'RSS', 'RSURF', 'SCALE', 'SEFFECT', 'SETUP_CLOCK_TIMER', 'SLEEP', 'SOURCE', 'SRS', 'STANDARD', 'STB_MODE',
        'STILL_PICTURE', 'STOP', 'SUB_TITLE', 'SVIDEO1', 'SVIDEO2', 'SVIDEO3', 'TOOLS', 'TOPMENU', 'TTX_MIX',
        'TTX_SUBFACE', 'TURBO', 'TV', 'TV_MODE', 'UP', 'VCHIP', 'VCR_MODE', 'VOLDOWN', 'VOLUP', 'WHEEL_LEFT',
        'WHEEL_RIGHT', 'W_LINK', 'YELLOW', 'ZOOM1', 'ZOOM2', 'ZOOM_IN', 'ZOOM_MOVE', 'ZOOM_OUT'
	);

	/**
	 * Remote constructor.
     *
     * @param string $host
     * @param AdapterInterface $cache
	 * @param LoggerInterface $logger
	 */
	public function __construct($host, AdapterInterface $cache, LoggerInterface $logger)
	{
        $this->host = $host;
	    $this->cache = $cache;
		$this->logger = $logger;
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
	 * @param string $sKey
     *
	 * @return bool
	 */
	private function validateKey($sKey): bool
    {
        if (substr($sKey, 0, 4) == 'KEY_') {
            $sKey = substr($sKey, 4);
        }

		return in_array($sKey,$this->aValidKeys);
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
		$aMessage = array(
			'method' => 'ms.remote.control',
			'params' => array(
				'Cmd' => 'Click',
				'DataOfCmd' => $sKey,
				'Option' => false,
				'TypeOfRemote' => 'SendRemoteKey'
			)
		);

		return json_encode($aMessage,JSON_PRETTY_PRINT);
	}

	/**
	 * Add a keypress to the queue.
     *
	 * @param string $sKey
	 * @param float $fDelay
	 */
	public function queueKey($sKey,$fDelay = 1.0): void
    {
        if (!$this->validateKey($sKey)) {
            throw new UnexpectedValueException("Invalid key: {$sKey}");
        }

		$this->queue[] = array(
		    'key' => $sKey,
            'delay' => $fDelay
        );
	}

	/**
	 * Clear any outstanding items in the key queue.
	 */
	public function clearQueue(): void
	{
		$this->queue = array();
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
