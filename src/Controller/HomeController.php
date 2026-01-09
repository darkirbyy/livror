<?php

declare(strict_types=1);

namespace App\Controller;

use App\Enum\DateFieldEnum;
use App\Repository\GameRepository;
use App\Repository\ReviewRepository;
use App\Service\HubUrlGenerator;
use App\Service\UserManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('', name: 'home_')]
class HomeController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(UserManager $userManager, GameRepository $gameRepository, ReviewRepository $reviewRepository): Response
    {
        $users = $userManager->findWithReview();

        $gamesInfo = $gameRepository->findLast(DateFieldEnum::ADD, $this->getParameter('app.home_game_limit'));
        $reviews = $reviewRepository->findLast(DateFieldEnum::ADD, $this->getParameter('app.home_review_limit'));

        $userManager->plugToGamesInfo($gamesInfo, $users);

        return $this->render('home/index.html.twig', [
            'gamesInfo' => $gamesInfo,
            'reviews' => $reviews,
        ]);
    }

    #[Route('/account', name: 'account', methods: ['GET'])]
    public function account(Request $request, HubUrlGenerator $hubUrlGenerator): Response
    {
        $referer = $request->headers->get('referer', $request->getSchemeAndHttpHost());
        $request->getSession()->set('hub/back-target-path', $referer);

        return $this->redirect($hubUrlGenerator->generateAccount(''));
    }
}
