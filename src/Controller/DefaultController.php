<?php

namespace App\Controller;

use App\Form\RemoteForm;
use App\Form\Type\RemoteType;
use App\Library\RemoteClient;
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
     * @var RemoteClient
     */
    private $remoteClient;

    /**
     * @var array
     */
    private $validKeys;

    /**
     * DefaultController constructor.
     *
     * @param RemoteClient $remoteClient
     * @param array $validKeys
     */
    public function __construct(RemoteClient $remoteClient, array $validKeys)
    {
        $this->remoteClient = $remoteClient;
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
        $form = $this->createForm(RemoteType::class, new RemoteForm(), [
            'choices' => $this->validKeys
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var RemoteForm $formData */
            $formData = $form->getData();

            $keys = $formData->getKeys();
            $keyString = implode(', ', $keys);

            try {
                $this->remoteClient->sendKeys($keys);

                $this->addFlash('success', "Successfully sent {$keyString}.");
            } catch (\Exception $e) {
                $this->addFlash('danger', "An error occurred while sending {$keyString}.");
            }
        }

        return $this->render('remote/index.html.twig', [
            'form' => $form->createView()
        ]);
    }
}
