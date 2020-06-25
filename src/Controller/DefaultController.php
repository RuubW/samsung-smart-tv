<?php

namespace App\Controller;

use App\Library\Remote;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class DefaultController.
 *
 * @package App\Controller
 */
class DefaultController extends AbstractController
{
    /**
     * @var Remote
     */
    private $remote;

    /**
     * DefaultController constructor.
     *
     * @param Remote $remote
     */
    public function __construct(Remote $remote)
    {
        $this->remote = $remote;
    }

    /**
     * Index action.
     *
     * @return Response
     */
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
            'key' => $key,
            'error' => $error
        ]);
    }
}