<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\FlashMessage;
use App\Dto\QueryParam;
use App\Entity\Main\Review;
use App\Form\ReviewType;
use App\Repository\GameRepository;
use App\Repository\ReviewRepository;
use App\Service\FormManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/review', name: 'review_')]
class ReviewController extends AbstractController
{
    // List and find reviews
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(#[MapQueryString] QueryParam $queryParam, GameRepository $gameRepo, ReviewRepository $reviewRepo, Request $request): Response
    {
        // Retrieve the connected user id
        $userId = $this->getUser()->getId();

        // Make the database query and get the corresponding reviews
        $reviews = $reviewRepo->findIndex($queryParam, $userId);

        // Prepare the data for the twig renderer
        $data = [
            'queryParam' => $queryParam,
            'reviews' => array_slice($reviews, 0, $queryParam->limit), // remove on result as we have fetched one more that configured
            'hasMore' => count($reviews) > $queryParam->limit, // determine if there is more games to fetch
            'cannotAdd' => 0 == $gameRepo->countNotCommented($userId),
        ];

        // Render only the review list block when the request comes from the JavaScript, otherwise render the whole page
        if ($request->isXmlHttpRequest()) {
            return $this->render('review/list.html.twig', $data);
        }

        return $this->render('review/index.html.twig', $data);
    }

    // Add new review
    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(GameRepository $gameRepo, Request $request, FormManager $fm): Response
    {
        $userId = $this->getUser()->getId();
        $gameId = 'GET' == $request->getMethod() ? $request->query->get('gameId') : null;
        if (0 == $gameRepo->countNotCommented($userId)) {
            throw new NotAcceptableHttpException();
        }

        $review = new Review();
        $form = $this->createForm(ReviewType::class, $review, ['userId' => $userId, 'gameId' => $gameId]);
        $form->handleRequest($request);

        $flashSuccess = new FlashMessage('review.index.flash.newReview', ['name' => $review->getGame()?->getName()]);
        if ($fm->validateAndPersist($form, $review, $flashSuccess)) {
            return $this->redirectToRoute('review_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('review/edit.html.twig', [
            'review' => $review,
            'form' => $form,
        ]);
    }

    // Edit an existing review
    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Review $review, Request $request, FormManager $fm): Response
    {
        $userId = $this->getUser()->getId();
        if ($userId !== $review->getUserId()) {
            throw new AccessDeniedHttpException();
        }

        $form = $this->createForm(ReviewType::class, $review);
        $form->handleRequest($request);

        $flashSuccess = new FlashMessage('review.index.flash.updateReview', ['name' => $review->getGame()->getName()]);
        if ($fm->validateAndPersist($form, $review, $flashSuccess)) {
            return $this->redirectToRoute('review_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('review/edit.html.twig', [
            'review' => $review,
            'form' => $form,
        ]);
    }

    // Delete a review
    #[Route('/{id}/delete', name: 'delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Review $review, FormManager $fm): Response
    {
        $userId = $this->getUser()->getId();
        if ($userId !== $review->getUserId()) {
            throw new AccessDeniedHttpException();
        }

        $flashSuccess = new FlashMessage('review.index.flash.deleteReview', ['name' => $review->getGame()->getName()]);
        if ($fm->checkTokenAndRemove('delete-review-' . $review->getId(), $review, $flashSuccess)) {
            return $this->redirectToRoute('review_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->redirectToRoute('review_edit', ['id' => $review->getId()], Response::HTTP_SEE_OTHER);
    }
}
