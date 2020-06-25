<?php

namespace App;

use Ratchet\RFC6455\Messaging\MessageInterface;
use React\EventLoop\Factory as ReactFactory;
use React\EventLoop\LoopInterface;
use Ratchet\Client\Connector;
use Psr\Log\LoggerInterface;
use Ratchet\Client\WebSocket;
use UnexpectedValueException;

/**
 * Remote control class for Samsung 2016+ TVs using the websocket interface
 * Based on https://github.com/benreidnet/samsungtv
 */
class Remote
{
	/**
	 * Logger for debugging
     *
     * @var LoggerInterface
	 */
	private $logger;

	/**
	 * Host to connect to
     *
     * @var string
	 */
	private $sHost;

	/**
	 * Port to connect to
     *
     * @var integer
	 */
    private $iPort = 8002;

	/**
     * Application name
     *
	 * @var string
	 */
	private $sAppName = 'PHP Remote';

	/**
     * Queue of keypresses
     *
	 * @var array
	 */
	private $aQueue = [];

    /**
     * Application authentication token
     *
     * @var string
     */
	private $sToken = '';

    const TOKEN_FILE_DIR = __DIR__ . '/../var/cache';

    const TOKEN_FILE_NAME = 'token';


	/**
	 * @var array
	 * Array of valid keys that can be sent. 
	 * This list is taken from https://github.com/Bntdumas/SamsungIPRemote/blob/master/samsungKeyCodes.txt
	 */
	private $aValidKeys = array(
		'0','1','2','3','4','5','6','7','8','9','11','12','4_3','16_9','3SPEED','AD','ADDDEL','ALT_MHP','ANGLE','ANTENA','ANYNET','ANYVIEW','APP_LIST','ASPECT',
		'AUTO_ARC_ANTENNA_AIR','AUTO_ARC_ANTENNA_CABLE','AUTO_ARC_ANTENNA_SATELLITE','AUTO_ARC_ANYNET_AUTO_START','AUTO_ARC_ANYNET_MODE_OK','AUTO_ARC_AUTOCOLOR_FAIL',
		'AUTO_ARC_AUTOCOLOR_SUCCESS','AUTO_ARC_CAPTION_ENG','AUTO_ARC_CAPTION_KOR','AUTO_ARC_CAPTION_OFF','AUTO_ARC_CAPTION_ON','AUTO_ARC_C_FORCE_AGING',
		'AUTO_ARC_JACK_IDENT','AUTO_ARC_LNA_OFF','AUTO_ARC_LNA_ON','AUTO_ARC_PIP_CH_CHANGE','AUTO_ARC_PIP_DOUBLE','AUTO_ARC_PIP_LARGE','AUTO_ARC_PIP_LEFT_BOTTOM',
		'AUTO_ARC_PIP_LEFT_TOP','AUTO_ARC_PIP_RIGHT_BOTTOM','AUTO_ARC_PIP_RIGHT_TOP','AUTO_ARC_PIP_SMALL','AUTO_ARC_PIP_SOURCE_CHANGE','AUTO_ARC_PIP_WIDE','AUTO_ARC_RESET',
		'AUTO_ARC_USBJACK_INSPECT','AUTO_FORMAT','AUTO_PROGRAM','AV1','AV2','AV3','BACK_MHP','BOOKMARK','CALLER_ID','CAPTION','CATV_MODE','CHDOWN','CH_LIST','CHUP','CLEAR',
		'CLOCK_DISPLAY','COMPONENT1','COMPONENT2','CONTENTS','CONVERGENCE','CONVERT_AUDIO_MAINSUB','CUSTOM','CYAN','DEVICE_CONNECT','DISC_MENU','DMA','DNET','DNIe','DNSe',
		'DOOR','DOWN','DSS_MODE','DTV','DTV_LINK','DTV_SIGNAL','DVD_MODE','DVI','DVR','DVR_MENU','DYNAMIC','ENTERTAINMENT','ESAVING','EXT1','EXT10','EXT11','EXT12','EXT13',
		'EXT14','EXT15','EXT16','EXT17','EXT18','EXT19','EXT2','EXT20','EXT21','EXT22','EXT23','EXT24','EXT25','EXT26','EXT27','EXT28','EXT29','EXT3','EXT30','EXT31','EXT32',
		'EXT33','EXT34','EXT35','EXT36','EXT37','EXT38','EXT39','EXT4','EXT40','EXT41','EXT5','EXT6','EXT7','EXT8','EXT9','FACTORY','FAVCH','FF','FF_','FM_RADIO','GAME','GREEN',
		'GUIDE','HDMI','HDMI1','HDMI2','HDMI3','HDMI4','HELP','HOME','ID_INPUT','ID_SETUP','INFO','INSTANT_REPLAY','LEFT','LINK','LIVE','MAGIC_BRIGHT','MAGIC_CHANNEL','MDC',
		'MENU','MIC','MORE','MOVIE1','MS','MTS','MUTE','NINE_SEPERATE','OPEN','PANNEL_CHDOWN','PANNEL_CHUP','PANNEL_ENTER','PANNEL_MENU','PANNEL_POWER','PANNEL_SOURCE',
		'PANNEL_VOLDOW','PANNEL_VOLUP','PANORAMA','PAUSE','PCMODE','PERPECT_FOCUS','PICTURE_SIZE','PIP_CHDOWN','PIP_CHUP','PIP_ONOFF','PIP_SCAN','PIP_SIZE','PIP_SWAP','PLAY',
		'PLUS100','PMODE','POWER','POWEROFF','POWERON','PRECH','PRINT','PROGRAM','QUICK_REPLAY','REC','RED','REPEAT','RESERVED1','RETURN','REWIND','REWIND_','RIGHT','RSS',
		'RSURF','SCALE','SEFFECT','SETUP_CLOCK_TIMER','SLEEP','SOURCE','SRS','STANDARD','STB_MODE','STILL_PICTURE','STOP','SUB_TITLE','SVIDEO1','SVIDEO2','SVIDEO3','TOOLS',
		'TOPMENU','TTX_MIX','TTX_SUBFACE','TURBO','TV','TV_MODE','UP','VCHIP','VCR_MODE','VOLDOWN','VOLUP','WHEEL_LEFT','WHEEL_RIGHT','W_LINK','YELLOW','ZOOM1','ZOOM2',
		'ZOOM_IN','ZOOM_MOVE','ZOOM_OUT'
	);

	/**
	 * Remote constructor.
     *
	 * @param LoggerInterface $logger
	 */
	public function __construct(LoggerInterface $logger)
	{
		$this->logger = $logger;

		$this->loadToken();
	}

	/**
	 * Set the host to connect to .
	 * Should probably be an IP as the libraries use global DNS rather than your local resolver.
     *
	 * @param string $sHost
     *
	 * @return Remote
	 */
	public function setHost($sHost): Remote
	{
		$this->sHost = $sHost;

		return $this;
	}

	/**
	 * Set the port to connect to (defaults to 8002).
     *
	 * @param int $iPort
     *
	 * @return Remote
	 */
	public function setPort($iPort): Remote
	{
		$this->iPort = $iPort;

		return $this;
	}

	/**
	 * Set the application name to identify this App to the TV as.
	 * (You may need to authorised this application through the TV interface)
     *
	 * @param string $sAppName
     *
	 * @return Remote
	 */
	public function setAppName($sAppName): Remote
	{
		$this->sAppName = $sAppName;

		return $this;
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

		$this->aQueue[] = array(
		    'key' => $sKey,
            'delay' => $fDelay
        );
	}

	/**
	 * Clear any outstanding items in the key queue.
	 */
	public function clearQueue(): void
	{
		$this->aQueue = array();
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
		$this->queueKey($sKey,$fDelay);
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
		$aKeyDef = array_pop($this->aQueue);
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
		if (count($this->aQueue) == 0) {
			$this->logger->warn('No keys to send');
			return;
		}

		$tokenStr = '';
		if (strlen($this->sToken) > 0) {
		    $tokenStr = "&token={$this->sToken}";
        }

		$sAppName = utf8_encode(base64_encode($this->sAppName));
		// ws and port 8001 for non-secure, wss and port 8002 for secure
		$sURL = "wss://{$this->sHost}:{$this->iPort}/api/v2/channels/samsung.remote.control?name={$sAppName}{$tokenStr}";

		$this->logger->debug("Connecting to {$sURL}");

		$loop = ReactFactory::create();
		$connector = new Connector($loop, null, [
		    'verify_peer' => false,
            'verify_peer_name' => false
        ]);
		$subProtocols = [];
		$headers = [];
		$connector($sURL, $subProtocols, $headers)->then(function(WebSocket $conn) use ($loop) {
			$conn->on('message', function(MessageInterface $msg) use ($conn, $loop) {
				$oMsg = json_decode($msg);
				if ($oMsg->event == 'ms.channel.connect') {
                    if (property_exists($oMsg->data, 'token')) {
                        $this->saveToken($oMsg->data->token);
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

    /**
     * Load the authentication token from the disk.
     */
	private function loadToken(): void
    {
        if (!file_exists(self::TOKEN_FILE_DIR . '/' . self::TOKEN_FILE_NAME)) {
            return;
        }

        $this->sToken = file_get_contents(self::TOKEN_FILE_DIR . '/' . self::TOKEN_FILE_NAME);
    }

    /**
     * Saves the authentication token to the disk,
     *
     * @param string $sToken
     */
    private function saveToken(string $sToken): void
    {
        if (!file_exists(self::TOKEN_FILE_DIR)) {
            mkdir(self::TOKEN_FILE_DIR, 755, true);
        }

        file_put_contents(self::TOKEN_FILE_DIR . '/' . self::TOKEN_FILE_NAME, $sToken);

        $this->sToken = $sToken;
    }
}
