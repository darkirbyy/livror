<?php

namespace App\Controller;

use App\Entity\Game;
use App\Form\GameType;
use App\Repository\GameRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;

#[Route('/game')]
class GameController extends AbstractController
{
    #[Route('', name: 'app_game_index')]
    public function index(GameRepository $gameRepository): Response
    {
        $games = $gameRepository->findAll();

        return $this->render('game/index.html.twig', [
            'games' => $games,
        ]);
    }

    #[Route('/new', name: 'app_game_new')]
    #[Route('/{id}/edit', name: 'app_game_edit', requirements: ['id' => '\d+'])]
    public function new(?Game $game, Request $request, EntityManagerInterface $entityManager): Response
    {
        $game ??= new Game();
        $form = $this->createForm(GameType::class, $game);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->getClickedButton() === $form->get('steamSearch')) {
                $game->setGenres('lalala');
                $form = $this->createForm(GameType::class, $game);
            } elseif ($form->getClickedButton() === $form->get('submit')) {
                $entityManager->persist($game);
                $entityManager->flush();

                // add flash
                return $this->redirectToRoute('app_game_index');
            } else {
                // add flash
            }
        }

        return $this->render('game/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[IsCsrfTokenValid('delete-game', tokenKey: 'token-delete')]
    #[Route('/{id}/delete', name: 'app_game_delete', requirements: ['id' => '\d+'])]
    public function delete(Game $game, Request $request, EntityManagerInterface $entityManager): Response
    {
        $entityManager->remove($game);
        $entityManager->flush();

        // Add flash

        return $this->redirectToRoute('app_game_index');
    }

    #[Route('/test', name: 'app_game_test')]
    public function test(): Response
    {
        return $this->render('game/test.html.twig', []);
    }
}
