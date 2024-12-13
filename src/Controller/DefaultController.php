<?php

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DefaultController extends AbstractController
{
    #[Route('/', name: 'default')]
    public function index(Connection $connection): Response
    {
        try {
            $connection->getDatabase();
            $db_test = true;
        } catch (\Exception $e) {
            $db_test = false;
        }

        return $this->render('default/index.html.twig', [
            'db_test' => $db_test,
        ]);
    }

    #[Route('/turbo', name: 'turbo')]
    public function turbo(): Response
    {
        return $this->render('default/turbo.html.twig', []);
    }
}
