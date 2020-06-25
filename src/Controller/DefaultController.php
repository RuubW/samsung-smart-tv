<?php

namespace App\Controller;

use App\Library\Remote;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends AbstractController
{
    private $remote;

    public function __construct(Remote $remote)
    {
        $this->remote = $remote;
    }

    public function index(): Response
    {
        $key = isset($_GET['key']) ? 'KEY_' . strtoupper($_GET['key']) : 'KEY_HOME';

        $error = false;
        try {
            $this->remote->sendKey($key);
        } catch (\Exception $e) {
            $error = $e->getMessage();
        }

        return $this->render('remote/index.html.twig', [
            'key' => $sKey,
            'error' => $error
        ]);
    }
}