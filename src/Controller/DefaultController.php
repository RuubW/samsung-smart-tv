<?php

namespace App\Controller;

use App\Remote;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Symfony\Component\HttpFoundation\Response;

class DefaultController
{
    public function index(): Response
    {
        $oLogger = new Logger('remote');
        $oLogger->pushHandler(new StreamHandler('php://stdout', Logger::DEBUG));

        $sIP = getenv('TV_IP');
        $sKey = isset($_GET['key']) ? 'KEY_' . strtoupper($_GET['key']) : 'KEY_HOME';

        $oRemote = new Remote($oLogger);
        $oRemote->setHost($sIP);

        try {
            $oRemote->sendKey($sKey);

            $response = "Sent the {$sKey} request.";
        } catch (\Exception $e) {
            $response = "Error: {$e->getMessage()}";
        }

        return new Response(
            $response
        );
    }
}