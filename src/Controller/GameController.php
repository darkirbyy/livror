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
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/game')]
class GameController extends AbstractController
{
    #[Route('', name: 'app_game_index', methods: ['GET'])]
    public function index(GameRepository $gameRepo, Request $request): Response
    {
        $sortField = $request->query->getString('sortField', 'name');
        $sortOrder = $request->query->getString('sortOrder', 'asc');
        $firstResult = $request->query->getInt('firstResult', 0);
        $maxResults = $this->getParameter('app.max_results');

        $games = $gameRepo->findSortLimit($sortField, $sortOrder, $firstResult, $maxResults);

        $data = [
            'games' => array_slice($games, 0, $maxResults),
            'hasMore' => count($games) > $maxResults,
            'searchParam' => [
                'sortField' => $sortField,
                'sortOrder' => $sortOrder,
                'firstResult' => $firstResult,
            ],
        ];

        if ($request->isXmlHttpRequest()) {
            return $this->render('game/list.html.twig', $data);
        }

        return $this->render('game/index.html.twig', $data);
    }

    #[Route('/new', name: 'app_game_new', methods: ['GET', 'POST'])]
    #[Route('/{id}/edit', name: 'app_game_edit', methods: ['GET, POST'], requirements: ['id' => '\d+'])]
    public function new(?Game $game, Request $request, EntityManagerInterface $em, TranslatorInterface $trans, SteamSearchService $steamSearch): Response
    {
        // Handling route: creating new game or updating existing one
        $isNewGame = str_ends_with($request->attributes->get('_route'), 'new');
        if (empty($game)) {
            if ($isNewGame) {
                $game = new Game();
            } else {
                throw new NotFoundHttpException(Game::class . ' object not found.');
            }
        }

        // Handling steam search with steamId query parameter
        $steamId = $request->query->get('steamId');
        $steamIdError = null;
        if (null != $steamId) {
            if (!\ctype_digit($steamId)) {
                $steamIdError = $trans->trans('game.error.steamId.invalid', [], 'validators');
            } else {
                $steamSearch->fetchSteamGame((int) $steamId);
                if (SteamSearchStatusEnum::OK === $steamSearch->getStatus()) {
                    $game = $steamSearch->fillGame($game);
                    $this->addFlash('success', $trans->trans('game.new.flash.steamSearch.success'));
                } elseif (SteamSearchStatusEnum::NOT_FOUND === $steamSearch->getStatus()) {
                    $steamIdError = $trans->trans('game.error.steamId.notFound', [], 'validators');
                } else {
                    $this->addFlash('danger', $trans->trans('game.new.flash.steamSearch.fail'));
                }
            }
        }

        // Create the form and fill the non-mapped typePrice field depending on the value of fullPrice
        $form = $this->createForm(GameType::class, $game, ['currency' => $this->getParameter('app.currency')]);
        $typePrice = TypePriceEnum::fromPrice($game->getFullPrice());
        $form->get('typePrice')->setData($typePrice);
        TypePriceEnum::PAYING !== $typePrice ? $form->get('fullPrice')->setData(null) : null;

        // Fill the form with the request data and add custom steam error id needed
        $form->handleRequest($request);
        null != $steamIdError ? $form->get('steamId')->addError(new FormError($steamIdError)) : null;

        // If the form is submitted and valid : recalculate the fullPrice from the non-mapped typePrice field, then persist and send flash
        if ($form->isSubmitted() && $form->isValid()) {
            $game->setFullPrice(TypePriceEnum::toPrice($form->get('typePrice')->getData(), $game->getFullPrice()));

            $em->persist($game);
            $em->flush();

            if ($isNewGame) {
                $this->addFlash('success', $trans->trans('game.index.flash.newGame', ['name' => $game->getName()]));
            } else {
                $this->addFlash('success', $trans->trans('game.index.flash.updateGame', ['name' => $game->getName()]));
            }

            return $this->redirectToRoute('app_game_index');
        }

        return $this->render('game/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[IsCsrfTokenValid('delete-game', tokenKey: 'token-delete')]
    #[Route('/{id}/delete', name: 'app_game_delete', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function delete(Game $game, EntityManagerInterface $em, TranslatorInterface $trans): Response
    {
        $em->remove($game);
        $em->flush();

        $this->addFlash('success', $trans->trans('game.index.flash.deleteGame', ['name' => $game->getName()]));

        return $this->redirectToRoute('app_game_index');
    }
}
