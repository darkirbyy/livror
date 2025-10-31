<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\GameIndex;
use App\Entity\Main\Review;
use App\Repository\UserRepository;

/**
 * Service to retrieve and link user object to reviews.
 */
class UserConnector
{
    public function __construct(private UserRepository $userRepo)
    {
    }

    public function toGamesIndex(array &$gamesIndex): void
    {
        // Extract and flatten all reviews of each game
        $reviews = array_merge(...array_map(fn (GameIndex $g) => $g->getGame()->getReviews()->toArray(), $gamesIndex));

        // Retrieve the users if possible
        $usersId = array_unique(array_map(fn (Review $r) => $r->getUserId(), $reviews));
        $users = $this->userRepo->byUsersId($usersId);

        // Plug the user in each of the entity Review
        array_walk($reviews, fn (Review $r) => $r->setUser($users[$r->getUserId()]));
    }
}
