<?php

namespace App\Controller;

use App\Form\Type\RemoteType;
use App\Library\Remote;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
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
     * @var array
     */
    private $validKeys;

    /**
     * DefaultController constructor.
     *
     * @param Remote $remote
     * @param array $validKeys
     */
    public function __construct(Remote $remote, array $validKeys)
    {
        $this->remote = $remote;
        $this->validKeys = $validKeys;
    }

    /**
     * Index action.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function index(Request $request): Response
    {
        $formRemote = new \App\Form\Remote();

        $form = $this->createForm(RemoteType::class, $formRemote, [
            'choices' => $this->validKeys
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $formRemote = $form->getData();
            $key = $formRemote->getKey();

            try {
                $this->remote->sendKey("KEY_{$key}");

                $this->addFlash('success', "Successfully sent the {$key} request.");
            } catch (\Exception $e) {
                $this->addFlash('danger', "An error occurred while sending the {$key} request.");
            }
        }

        return $this->render('remote/index.html.twig', [
            'form' => $form->createView()
        ]);
    }
}
