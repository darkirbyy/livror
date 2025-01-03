<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Game;
use App\Enum\SteamSearchStatusEnum;
use App\Enum\TypePriceEnum;
use App\Form\GameType;
use App\Repository\GameRepository;
use App\Service\SteamSearchService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
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
    public function new(
        ?int $id,
        Request $request,
        GameRepository $gameRepository,
        EntityManagerInterface $entityManager,
        TranslatorInterface $translator,
        SteamSearchService $steamSearch,
    ): Response {
        // Handling route: creating new game or updating existing one
        $newGame = null == $id;
        if (!$newGame) {
            $game = $gameRepository->find($id);
            if (null == $game) {
                $this->addFlash('warning', $translator->trans('game.page.index.flash.invalidGame'));

                return $this->redirectToRoute('app_game_index');
            }
        } else {
            $game ??= new Game();
        }

        // Handling steam search with steamId query parameter
        $steamId = $request->query->get('steamId');
        if (null != $steamId) {
            if (!\ctype_digit($steamId)) {
                $this->addFlash('warning', $translator->trans('game.page.new.flash.steamSearch.invalid'));
            } else {
                $steamSearch->fetchSteamGame((int) $steamId);
                if (SteamSearchStatusEnum::OK === $steamSearch->getStatus()) {
                    $game = $steamSearch->fillGame($game);
                    $this->addFlash('success', $translator->trans('game.page.new.flash.steamSearch.success'));
                } elseif (SteamSearchStatusEnum::NOT_FOUND === $steamSearch->getStatus()) {
                    $this->addFlash('warning', $translator->trans('game.page.new.flash.steamSearch.notFound'));
                } else {
                    $this->addFlash('danger', $translator->trans('game.page.new.flash.steamSearch.fail'));
                }
            }
        }

        $form = $this->createForm(GameType::class, $game);
        $this->setTypePriceFromFullPrice($form, $game);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->setFullPriceFromTypePrice($form, $game);
            $entityManager->persist($game);
            $entityManager->flush();
            // TODO : ajout/modifier dépend selon !
            $this->addFlash('success', $translator->trans('game.page.index.flash.newGame'));

            return $this->redirectToRoute('app_game_index');
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

        // TODO : ajout trad !
        $this->addFlash('success', 'Le jeu a été supprimé !');

        return $this->redirectToRoute('app_game_index');
    }

    private function setTypePriceFromFullPrice(FormInterface $form, ?Game $game): void
    {
        $typePrice = TypePriceEnum::fromPrice($game->getFullPrice());
        $form->get('typePrice')->setData($typePrice);

        if (TypePriceEnum::PAYING !== $typePrice) {
            $form->get('fullPrice')->setData(null);
        }
    }

    private function setFullPriceFromTypePrice(FormInterface $form, ?Game $game): void
    {
        $game->setFullPrice(TypePriceEnum::toPrice($form->get('typePrice')->getData(), $game->getFullPrice()));
    }
}
