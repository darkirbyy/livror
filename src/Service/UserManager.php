<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\GameIndex;
use App\Entity\Main\Review;
use App\Repository\ReviewRepository;
use App\Repository\UserRepository;

/**
 * Service to retrieve and link user object to reviews.
 */
class UserManager
{
    public function __construct(private UserRepository $userRepo, private ReviewRepository $reviewRepo)
    {
    }

    /**
     * Extract and flatten all reviews of each game, then plug the user.
     *
     * @param array $gamesIndex list of gameIndex DTO with each review with null user
     * @param array $users      list of all users necessary to plug
     */
    public function plugToGamesIndex(array &$gamesIndex, array $users): void
    {
        $reviews = array_merge(...array_map(fn (GameIndex $g) => $g->getGame()->getReviews()->toArray(), $gamesIndex));
        $this->plugToReviews($reviews, $users);
    }

    /**
     * Plug the user in each of the entity Review using the userId field.
     *
     * @param array $reviews list of Review entity with null user
     * @param array $users   list of all users necessary to plug
     */
    public function plugToReviews(array &$reviews, array $users): void
    {
        array_walk($reviews, fn (Review $r) => $r->setUser($users[$r->getUserId()]));
    }

    /**
     * Find all users that have commented at least one game.
     */
    public function findWithReview(): array
    {
        $usersId = $this->reviewRepo->findUsersId();
        $users = $this->userRepo->byUsersId($usersId);

        return $users;
    }
}
