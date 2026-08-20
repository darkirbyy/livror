<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\GameInfo;
use App\Dto\User;
use App\Dto\UserInfo;
use App\Entity\Review;
use App\Repository\ReviewRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Service to retrieve and link user DTO to reviews.
 */
class UserManager
{
    public function __construct(
        private Security $security,
        private ReviewRepository $reviewRepo,
        private KeycloakManagerInterface $keycloakManager,
        private TranslatorInterface $trans,
    ) {}

    /**
     * Retrieve all the users and the numberReviews associated, or anonymous user for not authorized one.
     *
     * @return array the list of users
     */
    public function getUsersInfo(): array
    {
        $usersInfo = [];
        foreach ($this->keycloakManager->getUsersAuthorized() as $userUuid => $user) {
            $usersInfo[$userUuid] = new UserInfo($user, 0);
        }

        foreach ($this->reviewRepo->countByUserUuid() as $countByUserUuid) {
            $userUuid = $countByUserUuid['userUuid']->toString();
            if (array_key_exists($userUuid, $usersInfo)) {
                $usersInfo[$userUuid]->numberReviews = $countByUserUuid['numberReviews'];
            } else {
                $deletedUsername = $this->trans->trans('layout.deletedUser');
                $deletedUser = new User($countByUserUuid['userUuid'], $deletedUsername, '');
                $usersInfo[$userUuid] = new UserInfo($deletedUser, $countByUserUuid['numberReviews']);
            }
        }

        return $usersInfo;
    }

    /**
     * Retrieve the user given by an UUID as a local User DTO object.
     *
     * @return User the fecthed user or null if not found
     */
    public function getUserByUuid(string $userUuid): ?User
    {
        $usersAuthorized = $this->keycloakManager->getUsersAuthorized();

        return array_key_exists($userUuid, $usersAuthorized) ? $usersAuthorized[$userUuid] : null;
    }

    /**
     * Retrieve the current connected user as a local user DTO object.
     *
     * @return ?User the connected user or null if not connected
     */
    public function getUserConnected(): ?User
    {
        $userUuid = $this->security->getUser()?->getId();

        return !is_null($userUuid) ? $this->getUserByUuid($userUuid) : null;
    }

    /**
     * Extract all reviews of a gameInfo DTO, then plug the user.
     *
     * @param GameInfo $gameInfo  one gameInfo DTO with each review with null user
     * @param array    $usersInfo list of all usersInfo necessary to plug
     */
    public function plugToGameInfo(GameInfo $gameInfo, array $usersInfo): void
    {
        $reviews = $gameInfo->game->getReviews()->toArray();
        $this->plugToReviews($reviews, $usersInfo);
    }

    /**
     * Extract and flatten all reviews of each game inside a list of gameInfo DTOs, then plug the user.
     *
     * @param array $gamesInfo list of gameInfo DTO with each review with null user
     * @param array $usersInfo list of all usersInfo necessary to plug
     */
    public function plugToGamesInfo(array &$gamesInfo, array $usersInfo): void
    {
        $reviews = array_merge(...array_map(fn(GameInfo $g) => $g->game->getReviews()->toArray(), $gamesInfo));
        $this->plugToReviews($reviews, $usersInfo);
    }

    /**
     * Plug the user in each of the entity Review using the userUuid field.
     *
     * @param array $reviews   list of Review entity with null user
     * @param array $usersInfo list of all usersInfo necessary to plug
     */
    public function plugToReviews(array &$reviews, array $usersInfo): void
    {
        array_walk($reviews, fn(Review $r) => $r->setUserInfo($usersInfo[$r->getUserUuid()->toString()]));
    }
}
