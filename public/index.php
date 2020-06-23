<?php

/**
 * Simple REST to websocket bridge to present the remote control interface as
 * service that can be called via POST from other home automation systems
 * without them needing to be websocket aware
 */

require __DIR__ . '/../vendor/autoload.php';

use SamsungTV\Remote;
use Monolog\Logger;
use Monolog\Handler\ErrorLogHandler;
use Symfony\Component\Yaml\Yaml;


$oLogger = new Logger("remote");
$oLogger->pushHandler(new ErrorLogHandler(ErrorLogHandler::OPERATING_SYSTEM,logger::INFO));
//$oLogger->pushHandler(new StreamHandler('php://stdout', Logger::DEBUG));

$sKey = 'KEY_HOME';

$oRemote = new Remote($oLogger);
$oRemote->setHost("192.168.2.1"); // 192.168.10.36
$oRemote->sendKey($sKey);

echo "Sent the {$sKey} request.";