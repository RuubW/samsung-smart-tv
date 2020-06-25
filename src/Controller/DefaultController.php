<?php

namespace App\Controller;

use App\Remote;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends AbstractController
{
    public function index(): Response
    {
        $oLogger = new Logger('remote');
        $oLogger->pushHandler(new StreamHandler('php://stdout', Logger::DEBUG));

        $sIP = getenv('TV_IP');
        $sKey = isset($_GET['key']) ? 'KEY_' . strtoupper($_GET['key']) : 'KEY_HOME';

        $oRemote = new Remote($oLogger);
        $oRemote->setHost($sIP);

        $error = false;
        try {
            $oRemote->sendKey($sKey);
        } catch (\Exception $e) {
            $error = $e->getMessage();
        }

        return $this->render('remote/index.html.twig', [
            'key' => $sKey,
            'error' => $error
        ]);
    }
}