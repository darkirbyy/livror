<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Dto\GameInfo;
use App\Dto\User;
use App\Entity\Game;
use App\Entity\Review;
use App\Repository\ReviewRepository;
use App\Repository\UserRepository;
use App\Service\KeycloakManagerInterface;
use App\Service\UserManager;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\Attributes as PU;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Uid\Uuid;

#[PU\AllowMockObjectsWithoutExpectations]
final class UserManagerTest extends TestCase
{
    private Security $security;
    private ReviewRepository $reviewRepo;
    private KeycloakManagerInterface $keycloakManager;

    private UserManager $userManager;

    public function setUp(): void
    {
        $this->security = $this->createMock(Security::class);
        $this->reviewRepo = $this->createMock(ReviewRepository::class);
        $this->keycloakManager = $this->createMock(KeycloakManagerInterface::class);

        $this->userManager = new UserManager($this->security, $this->reviewRepo, $this->keycloakManager);
    }

    #[PU\Test]
    public function plugToGameInfo(): void
    {
        [$reviews, $users] = $this->prepareReviewsAndUsers();

        $game = $this->createMock(Game::class);
        $game->expects($this->once())->method('getReviews')->willReturn(new ArrayCollection($reviews));
        $gameInfo = new GameInfo($game, 0, null, null, null);

        $this->userManager->plugToGameInfo($gameInfo, $users);
    }

    #[PU\Test]
    public function plugToGamesInfo(): void
    {
        [$reviews, $users] = $this->prepareReviewsAndUsers();

        $game1 = $this->createMock(Game::class);
        $game1->expects($this->once())->method('getReviews')->willReturn(new ArrayCollection($reviews));
        $gameInfo1 = new GameInfo($game1, 0, null, null, null);
        $game2 = $this->createMock(Game::class);
        $game2->expects($this->once())->method('getReviews')->willReturn(new ArrayCollection());
        $gameInfo2 = new GameInfo($game2, 0, null, null, null);
        $gamesInfo = [$gameInfo1, $gameInfo2];

        $this->userManager->plugToGamesInfo($gamesInfo, $users);
    }

    #[PU\Test]
    public function plugToReviews(): void
    {
        [$reviews, $users] = $this->prepareReviewsAndUsers();
        $this->userManager->plugToReviews($reviews, $users);
    }

    // #[PU\Test]
    // public function getUserList(): void
    // {
    //     $usersId = [['userId' => 1, 'numberReviews' => 5], ['userId' => 2, 'numberReviews' => 10], ['userId' => 4, 'numberReviews' => 2]];

    //     $this->reviewRepo->expects($this->once())->method('countByUserUuid')->willReturn($usersId);
    //     $this->userRepo->expects($this->once())->method('byUsersId')->with(array_column($usersId, 'userId'));

    //     $this->userManager->getUserList();
    // }

    public static function generateValues(): array
    {
        return [
            'empty no param' => ['', [], ''],
            'empty with params' => ['', ['token' => 'abcd'], '?token=abcd'],
            'route no param' => ['/check', [], '/check'],
            'route with params' => ['/logout', ['token' => 'abcd'], '/logout?token=abcd'],
        ];
    }

    public function prepareReviewsAndUsers(): array
    {
        $user1 = new User(Uuid::v4(), 'user1', '');
        $user2 = new User(Uuid::v4(), 'user2', '');
        $user4 = new User(Uuid::v4(), 'user4', '');
        $users = [
            $user1->uuid->toString() => $user1,
            $user2->uuid->toString() => $user2,
            Uuid::v4()->toString() => null,
            $user4->uuid->toString() => $user4,
        ];

        $review1 = $this->createMock(Review::class);
        $review1->expects($this->once())->method('getUserUuid')->willReturn($user1->uuid);
        $review1->expects($this->once())->method('setUser')->with($user1);
        $review4 = $this->createMock(Review::class);
        $review4->expects($this->once())->method('getUserUuid')->willReturn($user4->uuid);
        $review4->expects($this->once())->method('setUser')->with($user4);
        $reviews = [1 => $review1, 4 => $review4];

        return [$reviews, $users];
    }
}
