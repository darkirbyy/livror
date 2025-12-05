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

    public function toGamesIndex(array &$gamesIndex, array $users): void
    {
        // Extract and flatten all reviews of each game
        $reviews = array_merge(...array_map(fn (GameIndex $g) => $g->getGame()->getReviews()->toArray(), $gamesIndex));
        $this->toReviews($reviews, $users);
    }

    public function toReviews(array &$reviews, array $users): void
    {
        // Plug the user in each of the entity Review
        array_walk($reviews, fn (Review $r) => $r->setUser($users[$r->getUserId()]));
    }

    public function findWithReview(): array
    {
        $usersId = $this->reviewRepo->findUsersId();
        $users = $this->userRepo->byUsersId($usersId);
        // usort($users, fn(User $u1, User $u2) => $u1->getUsername() <=> $u2->getUsername());

        return $users;
    }
}
