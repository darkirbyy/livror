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
use Symfony\Component\ExpressionLanguage\Expression;
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
    // List and find games
    #[Route('', name: 'game_index', methods: ['GET'])]
    public function index(GameRepository $gameRepo, Request $request): Response
    {
        // Parse all the query parameters
        $sortField = $request->query->getString('sortField', 'name');
        $sortOrder = $request->query->getString('sortOrder', 'asc');
        $firstResult = $request->query->getInt('firstResult', 0);
        $maxResults = $this->getParameter('app.max_results');

        // Make the database query and get the corresponding games
        $games = $gameRepo->findSortLimit($sortField, $sortOrder, $firstResult, $maxResults);

        // Prepare the data for the twig renderer
        $data = [
            'games' => array_slice($games, 0, $maxResults), // remove on result as we have fetched one more that configured
            'hasMore' => count($games) > $maxResults, // determine if there is more games to fetch
            'searchParam' => [
                'sortField' => $sortField,
                'sortOrder' => $sortOrder,
                'firstResult' => $firstResult,
            ],
        ];

        // Render only the game list block when the request comes from the JavaScript, otherwise render the whole page
        if ($request->isXmlHttpRequest()) {
            return $this->render('game/list.html.twig', $data);
        }

        return $this->render('game/index.html.twig', $data);
    }

    // Edit or add new game
    #[Route('/new', name: 'game_new', methods: ['GET', 'POST'])]
    #[Route('/{id}/edit', name: 'game_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
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
                    $this->addFlash('success', ['message' => 'game.new.flash.steamSearch.success']);
                } elseif (SteamSearchStatusEnum::NOT_FOUND === $steamSearch->getStatus()) {
                    $steamIdError = $trans->trans('game.error.steamId.notFound', [], 'validators');
                } else {
                    $this->addFlash('danger', ['message' => 'game.new.flash.steamSearch.fail']);
                }
            }
        }

        // Create the form and fill the non-mapped typePrice field depending on the value of fullPrice
        $form = $this->createForm(GameType::class, $game, ['currency' => $this->getParameter('app.currency')]);
        $typePrice = TypePriceEnum::fromPrice($game->getFullPrice());
        $form->get('typePrice')->setData($typePrice);
        TypePriceEnum::PAYING !== $typePrice ? $form->get('fullPrice')->setData(null) : null;

        // Fill the form with the request data and add custom steam error if needed
        $form->handleRequest($request);
        null != $steamIdError ? $form->get('steamId')->addError(new FormError($steamIdError)) : null;

        // If the form is submitted and valid : recalculate the fullPrice from the non-mapped typePrice field, then persist and send flash
        if ($form->isSubmitted() && $form->isValid()) {
            $game->setFullPrice(TypePriceEnum::toPrice($form->get('typePrice')->getData(), $game->getFullPrice()));

            $em->persist($game);
            $em->flush();

            if ($isNewGame) {
                $this->addFlash('success', ['message' => 'game.index.flash.newGame', 'params' => ['name' => $game->getName()]]);
            } else {
                $this->addFlash('success', ['message' => 'game.index.flash.updateGame', 'params' => ['name' => $game->getName()]]);
            }

            return $this->redirectToRoute('game_index');
        }

        return $this->render('game/new.html.twig', [
            'form' => $form,
        ]);
    }

    // Delete a game
    #[IsCsrfTokenValid(new Expression('"delete-" ~ args["game"].getId()'), tokenKey: 'delete_token')]
    #[Route('/{id}/delete', name: 'game_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Game $game, EntityManagerInterface $em): Response
    {
        $em->remove($game);
        $em->flush();

        $this->addFlash('success', ['message' => 'game.index.flash.deleteGame', 'params' => ['name' => $game->getName()]]);

        return $this->redirectToRoute('game_index');
    }
}
