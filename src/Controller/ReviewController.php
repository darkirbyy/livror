<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\FlashMessage;
use App\Dto\QueryParam;
use App\Dto\UserInfo;
use App\Entity\Review;
use App\Form\ReviewType;
use App\Repository\GameRepository;
use App\Repository\ReviewRepository;
use App\Service\BackpathUrlGenerator;
use App\Service\FormManager;
use App\Service\UserManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Uid\Uuid;

#[Route('/review', name: 'review_')]
class ReviewController extends AbstractController
{
    // List and find reviews
    #[Route('/{uuid?}', name: 'index', methods: ['GET'], requirements: ['uuid' => Requirement::UUID])]
    public function index(
        ?string $uuid,
        #[MapQueryString] QueryParam $queryParam,
        UserManager $userManager,
        GameRepository $gameRepo,
        ReviewRepository $reviewRepo,
        Request $request,
    ): Response {
        // Retrieve the user from the route param, or the current user otherwise
        $user = !empty($uuid) ? $userManager->getUserByUuid($uuid) : $userManager->getUserConnected();

        // Make the database query and get the corresponding reviews
        $reviews = $reviewRepo->findIndex($queryParam, $user->uuid);
        $numbers = $reviewRepo->countIndex($queryParam, $user->uuid);
        $userManager->plugToReviews($reviews, [$user->uuid->toString() => new UserInfo($user,0)]);

        // Prepare the data for the twig renderer
        $data = [
            'queryParam' => $queryParam,
            'reviews' => array_slice($reviews, 0, $queryParam->limit), // remove on result as we have fetched one more that configured
            'hasMore' => count($reviews) > $queryParam->limit, // determine if there is more games to fetch
            'numbers' => $numbers,
            'cannotAdd' => 0 == $gameRepo->countWithoutReview($user->uuid),
            'user' => $user,
        ];

        // Render only the review list block when the request comes from the JavaScript, otherwise render the whole page
        if ($request->isXmlHttpRequest()) {
            return $this->render('review/_list.html.twig', $data);
        }

        return $this->render('review/index.html.twig', $data);
    }

    // Add new review
    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(GameRepository $gameRepo, UserManager $userManager, Request $request, FormManager $fm, BackpathUrlGenerator $backpathUrlGenerator): Response
    {
        // Retrieve the connected user and its uuid
        $user = $userManager->getUserConnected();
        $userUuid = $user->uuid;

        $gameId = 'GET' == $request->getMethod() ? $request->query->get('gameId') : null;
        if (0 == $gameRepo->countWithoutReview($userUuid)) {
            throw new \RuntimeException('No game available for user ' . $user->username . '.');
        }

        $review = new Review();
        $form = $this->createForm(ReviewType::class, $review, ['userUuid' => $userUuid, 'gameId' => $gameId]);
        $form->handleRequest($request);

        $flashSuccess = new FlashMessage('review.index.flash.newReview', ['name' => $review->getGame()?->getName()]);
        if ($fm->validateAndPersist($form, $review, $flashSuccess)) {
            return $this->redirect($backpathUrlGenerator->generate('review_index'), Response::HTTP_SEE_OTHER);
        }

        return $this->render('review/edit.html.twig', [
            'review' => $review,
            'form' => $form,
        ]);
    }

    // Edit an existing review
    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'], requirements: ['id' => Requirement::DIGITS])]
    #[IsGranted('edit', 'review')]
    public function edit(Review $review, Request $request, FormManager $fm, BackpathUrlGenerator $backpathUrlGenerator): Response
    {
        $form = $this->createForm(ReviewType::class, $review);
        $form->handleRequest($request);

        $flashSuccess = new FlashMessage('review.index.flash.updateReview', ['name' => $review->getGame()->getName()]);
        if ($fm->validateAndPersist($form, $review, $flashSuccess)) {
            return $this->redirect($backpathUrlGenerator->generate('review_index'), Response::HTTP_SEE_OTHER);
        }

        return $this->render('review/edit.html.twig', [
            'review' => $review,
            'form' => $form,
        ]);
    }

    // Delete a review
    #[Route('/{id}/delete', name: 'delete', methods: ['POST'], requirements: ['id' => Requirement::DIGITS])]
    #[IsGranted('delete', 'review')]
    public function delete(Review $review, FormManager $fm, BackpathUrlGenerator $backpathUrlGenerator): Response
    {
        $flashSuccess = new FlashMessage('review.index.flash.deleteReview', ['name' => $review->getGame()->getName()]);
        if ($fm->checkTokenAndRemove('delete', $review, $flashSuccess)) {
            return $this->redirect($backpathUrlGenerator->generate('review_index'), Response::HTTP_SEE_OTHER);
        }

        return $this->redirectToRoute('review_edit', ['id' => $review->getId()], Response::HTTP_SEE_OTHER);
    }
}
