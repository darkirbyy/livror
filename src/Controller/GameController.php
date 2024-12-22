<?php

namespace App\Controller;

use App\Entity\Game;
use App\Enum\SteamSearchStatusEnum;
use App\Form\GameType;
use App\Repository\GameRepository;
use App\Service\SteamSearchService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Contracts\Translation\TranslatorInterface;

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
    public function new(?Game $game, Request $request, EntityManagerInterface $entityManager, TranslatorInterface $translator, SteamSearchService $steamSearch): Response
    {
        $game ??= new Game();
        $form = $this->createForm(GameType::class, $game);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->getClickedButton() === $form->get('steamSearch')) {
                $steamId = $form->get('steamId')->getData();
                $steamSearch->fetchSteamGame($steamId);
                if (SteamSearchStatusEnum::OK === $steamSearch->getStatus()) {
                    $steamSearch->fillGameData($game);
                    $form = $this->createForm(GameType::class, $game);
                    $this->addFlash('success', $translator->trans('Les données du jeu ont été mise à jour depuis Steam !'));
                } elseif (SteamSearchStatusEnum::INVALID_ID === $steamSearch->getStatus()) {
                    $form->get('steamId')->addError(new FormError('L\'ID Steam saisi n\'est pas valide'));
                } else {
                    $this->addFlash('danger', $translator->trans('Une erreur s\'est produite en se connectant à Steam.'));
                }
            } elseif ($form->getClickedButton() === $form->get('submit')) {
                $entityManager->persist($game);
                $entityManager->flush();
                $this->addFlash('success', $translator->trans('Le jeu a été ajouté avec succès !'));

                return $this->redirectToRoute('app_game_index');
            } else {
                $this->addFlash('warning', 'Unknown action.');

                return $this->redirectToRoute('app_game_index');
            }
        }

        return $this->render('game/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[IsCsrfTokenValid('delete-game', tokenKey: 'token-delete')]
    #[Route('/{id}/delete', name: 'app_game_delete', requirements: ['id' => '\d+'])]
    public function delete(Game $game, EntityManagerInterface $entityManager): Response
    {
        $entityManager->remove($game);
        $entityManager->flush();

        $this->addFlash('success', $game->getName() . ' deleted!');

        return $this->redirectToRoute('app_game_index');
    }
}
