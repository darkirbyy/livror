<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\GameInfo;
use App\Dto\User;
use App\Entity\Review;
use App\Repository\ReviewRepository;
use LogicException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Uid\Uuid;

/**
 * Service to retrieve and link user DTO to reviews.
 */
class UserManager
{

    public function __construct(private Security $security, private ReviewRepository $reviewRepo, private KeycloakManagerInterface $keycloakManager) {}

    /**
     * Retrieve all the users with the numberReviews field completed, and anonymous user for not authorized one
     *
     * @return array   the list of users
     */
    public function getUserList(): array
    {
        // // TODO : rework, two functions (userinfos or user, add anonymous, optimize plug)
        // // Find all distinct users id among the reviews, adn the number of reviews for each one
        // $usersInfo = $this->reviewRepo->countByUserUuid();

        // // Create an array with all users that have reviewed and replace the authorized-ones
        // $users = array_fill_keys(array_column($usersInfo, 'userUuid'), new User(Uuid::v7(), 'user deleted', ''));
        // $users = array_replace($users, $this->keycloakManager->getUsersAuthorized());

        // // Plug the number of reviews in each User
        // foreach ($usersInfo as $userInfo) {
        //     $users[$userInfo['userUuid']->toString()]->numberReviews = $userInfo['numberReviews'];
        // }

        $users = [];
        foreach ($this->keycloakManager->getUsersAuthorized() as $user) {
            $users[$user->uuid->toString()] = $user;
        }

        return $users;
    }

    /**
     * Retrieve the user given by an UUID as a local User DTO object.
     *
     * @return User   the fecthed user or null if not found
     */
    public function getUserByUuid(Uuid $userUuid): ?User
    {
        return array_find($this->keycloakManager->getUsersAuthorized(), fn(User $u) => $u->uuid->toString() === $userUuid->toString());
    }

    /**
     * Retrieve the current connected user as a local user DTO object.
     *
     * @return ?User   the connected user or null if not connected
     */
    public function getUserConnected(): ?User
    {
        $userSecurity = $this->security->getUser();
        if (is_null($userSecurity)) {
            return null;
        }
        $usersAuthorized = $this->keycloakManager->getUsersAuthorized();
        $userSecurityUuid = $userSecurity->getId();
        if (!array_key_exists($userSecurityUuid, $usersAuthorized)) {
            return null;
        }

        return $usersAuthorized[$userSecurityUuid];
    }

    /**
     * Extract all reviews of a gameInfo DTO, then plug the user.
     *
     * @param GameInfo $gameInfo one gameInfo DTO with each review with null user
     * @param array    $users    list of all users necessary to plug
     */
    public function plugToGameInfo(GameInfo $gameInfo, array $users): void
    {
        $reviews = $gameInfo->game->getReviews()->toArray();
        $this->plugToReviews($reviews, $users);
    }

    /**
     * Extract and flatten all reviews of each game inside a list of gameInfo DTOs, then plug the user.
     *
     * @param array $gamesInfo list of gameInfo DTO with each review with null user
     * @param array $users     list of all users necessary to plug
     */
    public function plugToGamesInfo(array &$gamesInfo, array $users): void
    {
        $reviews = array_merge(...array_map(fn(GameInfo $g) => $g->game->getReviews()->toArray(), $gamesInfo));
        $this->plugToReviews($reviews, $users);
    }

    /**
     * Plug the user in each of the entity Review using the userUuid field.
     *
     * @param array $reviews list of Review entity with null user
     * @param array $users   list of all users necessary to plug
     */
    public function plugToReviews(array &$reviews, array $users): void
    {
        array_walk($reviews, fn(Review $r) => $r->setUser($users[$r->getUserUuid()->toString()]));
    }
}
