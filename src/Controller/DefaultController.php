<?php

namespace App\Controller;

use App\Form\RemoteForm;
use App\Form\Type\RemoteType;
use App\Library\RemoteClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class DefaultController.
 *
 * @package App\Controller
 */
class DefaultController extends AbstractController
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

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
     * @param TranslatorInterface $translator
     * @param RemoteClient $remoteClient
     * @param array $validKeys
     */
    public function __construct(
        TranslatorInterface $translator,
        RemoteClient $remoteClient,
        array $validKeys
    ) {
        $this->translator = $translator;
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
        // Prepare the form.
        $form = $this->createForm(RemoteType::class, new RemoteForm(), [
            'choices' => $this->validKeys
        ]);

        // Handle the form request.
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var RemoteForm $formData */
            $formData = $form->getData();

            // Retrieve the key(s).
            $keys = $formData->getKeys();

            try {
                // Send the key(s).
                $this->remoteClient->sendKeys($keys);

                $this->addFlash(
                    'success',
                    $this->translator->trans('remote.controller.success', [
                        'count' => count($keys),
                        'keys' => implode(', ', $keys)
                    ])
                );
            } catch (\Exception $e) {
                $this->addFlash(
                    'danger',
                    $this->translator->trans('remote.controller.error', [
                        'count' => count($keys),
                        'keys' => implode(', ', $keys)
                    ])
                );
            }
        }

        // Render the form.
        return $this->render('remote/index.html.twig', [
            'form' => $form->createView()
        ]);
    }
}
