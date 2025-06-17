<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Main\Review;
use App\Form\ReviewType;
use App\Repository\ReviewRepository;
use App\Service\FormManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/review', name: 'review_')]
class ReviewController extends AbstractController
{
    // List and find reviews
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(ReviewRepository $reviewRepo, Request $request): Response
    {
        // Parse all the query parameters
        $sortField = $request->query->getString('sortField', 'dateAdd');
        $sortOrder = $request->query->getString('sortOrder', 'desc');
        $firstResult = $request->query->getInt('firstResult', 0);
        $maxResults = $this->getParameter('app.max_results');

        // Make the database query and get the corresponding reviews
        $reviews = $reviewRepo->findSortLimit($sortField, $sortOrder, $firstResult, $maxResults);

        // Prepare the data for the twig renderer
        $data = [
            'reviews' => array_slice($reviews, 0, $maxResults), // remove on result as we have fetched one more that configured
            'hasMore' => count($reviews) > $maxResults, // determine if there is more games to fetch
            'searchParam' => [
                'sortField' => $sortField,
                'sortOrder' => $sortOrder,
                'firstResult' => $firstResult,
            ],
        ];

        // Render only the review list block when the request comes from the JavaScript, otherwise render the whole page
        if ($request->isXmlHttpRequest()) {
            return $this->render('review/list.html.twig', $data);
        }

        return $this->render('review/index.html.twig', $data);
    }

    // Add new review
    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, FormManager $fm): Response
    {
        $userId = $this->getUser()->getId();
        $review = new Review();

        $form = $this->createForm(ReviewType::class, $review, ['newReview' => true, 'userId' => $userId]);
        $form->handleRequest($request);

        $flashSuccess = ['message' => 'review.index.flash.newReview', 'params' => ['gameName' => $review->getGame()?->getName()]];
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

        $form = $this->createForm(ReviewType::class, $review, ['newReview' => false, 'userId' => $userId]);
        $form->handleRequest($request);

        $flashSuccess = ['message' => 'review.index.flash.updateReview', 'params' => ['gameName' => $review->getGame()->getName()]];
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
        $flashSuccess = ['message' => 'review.index.flash.deleteReview', 'params' => ['gameName' => $review->getGame()->getName()]];
        if ($fm->checkTokenAndRemove('delete-review-' . $review->getId(), $review, $flashSuccess)) {
            return $this->redirectToRoute('review_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->redirectToRoute('review_edit', ['id' => $review->getId()], Response::HTTP_SEE_OTHER);
    }
}
