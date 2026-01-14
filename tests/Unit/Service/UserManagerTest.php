<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Dto\GameInfo;
use App\Entity\Account\User;
use App\Entity\Main\Game;
use App\Entity\Main\Review;
use App\Repository\ReviewRepository;
use App\Repository\UserRepository;
use App\Service\UserManager;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\Attributes as PU;
use PHPUnit\Framework\TestCase;

#[PU\AllowMockObjectsWithoutExpectations]
final class UserManagerTest extends TestCase
{
    private $userRepo;
    private $reviewRepo;

    private $userManager;

    public function setUp(): void
    {
        $this->userRepo = $this->createMock(UserRepository::class);
        $this->reviewRepo = $this->createMock(ReviewRepository::class);

        $this->userManager = new UserManager($this->userRepo, $this->reviewRepo);
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

    #[PU\Test]
    public function findWithReview(): void
    {
        $usersId = [1, 2, 4];

        $this->reviewRepo->expects($this->once())->method('findUsersId')->willReturn($usersId);
        $this->userRepo->expects($this->once())->method('byUsersId')->with($usersId);

        $this->userManager->findWithReview();
    }

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
        $$user1 = $this->createMock(User::class);
        $user2 = $this->createMock(User::class);
        $user4 = $this->createMock(User::class);
        $users = [1 => $user1, 2 => $user2, 3 => null, 4 => $user4];

        $review1 = $this->createMock(Review::class);
        $review1->expects($this->once())->method('getUserId')->willReturn(1);
        $review1->expects($this->once())->method('setUser')->with($user1);
        $review4 = $this->createMock(Review::class);
        $review4->expects($this->once())->method('getUserId')->willReturn(4);
        $review4->expects($this->once())->method('setUser')->with($user4);
        $reviews = [1 => $review1, 4 => $review4];

        return [$reviews, $users];
    }
}
