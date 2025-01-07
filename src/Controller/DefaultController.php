<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DefaultController extends AbstractController
{
    #[Route('/', name: 'app_default_index')]
    public function index(): Response
    {
        return $this->render('default/index.html.twig', []);
        //    return $this->redirectToRoute('app_default_home');
    }

    // #[Route('/home', name: 'app_default_home')]
    // public function home(): Response
    // {
    //     return $this->render('default/home.html.twig', []);
    // }

    #[Route('/example', name: 'app_default_example')]
    public function example(): Response
    {
        return $this->render('default/example.html.twig', []);
    }
}
