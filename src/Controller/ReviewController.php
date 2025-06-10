<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Main\Review;
use App\Form\ReviewType;
use App\Repository\ReviewRepository;
use App\Service\SteamSearchService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/review', name: 'review_')]
class ReviewController extends AbstractController
{
    // List and find reviews
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(ReviewRepository $reviewRepo, Request $request): Response
    {
        // Make the database query and get the corresponding reviews
        $reviews = $reviewRepo->findAll();

        return $this->render('review/index.html.twig', [
            'reviews' => $reviews,
        ]);
    }

    // Edit or add new review
    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function new(?Review $review, Request $request, EntityManagerInterface $em, TranslatorInterface $trans, SteamSearchService $steamSearch): Response
    {
        // Handling route: creating new review or updating existing one
        $isNewReview = str_ends_with($request->attributes->get('_route'), 'new');
        if (empty($review)) {
            if ($isNewReview) {
                $review = new Review();
            } else {
                throw new NotFoundHttpException(Review::class . ' object not found.');
            }
        }

        $form = $this->createForm(ReviewType::class, $review);
        $form->handleRequest($request);

        // If the form is submitted and valid : recalculate the fullPrice from the non-mapped typePrice field, then persist and send flash
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($review);
            $em->flush();

            // if ($isNewReview) {
            //     $this->addFlash('success', ['message' => 'game.index.flash.newGame', 'params' => ['name' => $game->getName()]]);
            // } else {
            //     $this->addFlash('success', ['message' => 'game.index.flash.updateGame', 'params' => ['name' => $game->getName()]]);
            // }

            return $this->redirectToRoute('review_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('review/new.html.twig', [
            'form' => $form,
        ]);
    }

    // Delete a review
    #[IsCsrfTokenValid(new Expression('"delete-" ~ args["review"].getId()'), tokenKey: 'delete_token')]
    #[Route('/{id}/delete', name: 'delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Review $review, EntityManagerInterface $em): Response
    {
        $em->remove($review);
        $em->flush();

        // $this->addFlash('success', ['message' => 'review.index.flash.deleteGame', 'params' => ['name' => $review->getName()]]);

        return $this->redirectToRoute('review_index', [], Response::HTTP_SEE_OTHER);
    }
}
