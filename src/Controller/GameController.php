<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\FlashMessage;
use App\Dto\QueryParam;
use App\Entity\Main\Game;
use App\Form\GameType;
use App\Repository\GameRepository;
use App\Service\FormManager;
use App\Service\UserConnector;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/game', name: 'game_')]
class GameController extends AbstractController
{
    // List and find games
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(#[MapQueryString] QueryParam $queryParam, UserConnector $userConnector, GameRepository $gameRepo, Request $request): Response
    {
        // Fetch all distinct users that have written at least one review
        $users = $userConnector->findWithReview();

        // Make the database query and get the corresponding games, and link the users
        $gamesIndex = $gameRepo->findIndex($queryParam);
        $userConnector->toGamesIndex($gamesIndex, $users);

        // Prepare the data for the twig renderer
        $data = [
            'queryParam' => $queryParam,
            'users' => $users,
            'gamesIndex' => array_slice($gamesIndex, 0, $queryParam->limit), // remove one result as we have fetched one more that configured
            'hasMore' => count($gamesIndex) > $queryParam->limit, // determine if there is more games to fetch
        ];

        // Render only the game list block when the request comes from the JavaScript, otherwise render the whole page
        if ($request->isXmlHttpRequest()) {
            return $this->render('game/list.html.twig', $data);
        }

        return $this->render('game/index.html.twig', $data);
    }

    // Add new game
    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, FormManager $fm): Response
    {
        $game = new Game();
        $steamId = 'GET' == $request->getMethod() ? $request->query->get('steamId') : null;

        $form = $this->createForm(GameType::class, $game, ['steamId' => $steamId]);
        $form->handleRequest($request);

        $flashSuccess = new FlashMessage('game.index.flash.newGame');
        if ($fm->validateAndPersist($form, $game, $flashSuccess)) {
            $flashSuccess->setParams(['url' => $this->generateUrl('review_new', ['gameId' => $game->getId()]), 'name' => $game->getName()]);

            return $this->redirectToRoute('game_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('game/edit.html.twig', [
            'game' => $game,
            'form' => $form,
        ]);
    }

    // Edit an existing game
    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Game $game, Request $request, FormManager $fm): Response
    {
        $steamId = 'GET' == $request->getMethod() ? $request->query->get('steamId') : null;

        $form = $this->createForm(GameType::class, $game, ['steamId' => $steamId]);
        $form->handleRequest($request);

        $flashSuccess = new FlashMessage('game.index.flash.updateGame', ['name' => $game->getName()]);
        if ($fm->validateAndPersist($form, $game, $flashSuccess)) {
            return $this->redirectToRoute('game_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('game/edit.html.twig', [
            'game' => $game,
            'form' => $form,
        ]);
    }

    // Delete a game
    #[Route('/{id}/delete', name: 'delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Game $game, FormManager $fm): Response
    {
        $flashSuccess = new FlashMessage('game.index.flash.deleteGame', ['name' => $game->getName()]);
        if ($fm->checkTokenAndRemove('livror/delete', $game, $flashSuccess)) {
            return $this->redirectToRoute('game_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->redirectToRoute('game_edit', ['id' => $game->getId()], Response::HTTP_SEE_OTHER);
    }
}
