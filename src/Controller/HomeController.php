<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\HubUrlGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('', name: 'home_')]
class HomeController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('home/index.html.twig', []);
    }

    #[Route('/account', name: 'account', methods: ['GET'])]
    public function account(Request $request, HubUrlGenerator $hubUrlGenerator): Response
    {
        $referer = $request->headers->get('referer', $request->getSchemeAndHttpHost());
        $request->getSession()->set('hub/back-target-path', $referer);

        return $this->redirect($hubUrlGenerator->generateAccount(''));
    }
}
