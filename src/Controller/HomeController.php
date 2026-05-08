<?php

declare(strict_types=1);

namespace App\Controller;

use App\Enum\DateFieldEnum;
use App\Repository\GameRepository;
use App\Repository\ReviewRepository;
use App\Service\UserManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
        $userManager->plugToReviews($reviews, $users);

        return $this->render('home/index.html.twig', [
            'gamesInfo' => $gamesInfo,
            'reviews' => $reviews,
        ]);
    }
}
