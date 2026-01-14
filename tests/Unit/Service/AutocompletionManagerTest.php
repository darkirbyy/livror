<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Account\User;
use App\Entity\Main\Game;
use App\Entity\Main\Steam;
use App\Enum\SearchModeEnum;
use App\Repository\GameRepository;
use App\Repository\SteamRepository;
use App\Service\AutocompletionManager;
use App\Service\UserManager;
use PHPUnit\Framework\Attributes as PU;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;

#[PU\AllowMockObjectsWithoutExpectations]
final class AutocompletionManagerTest extends TestCase
{
    private static $autocompletionLimit = 20;
    private static $autocompletionMinLength = 5;
    private $security;
    private $steamRepo;
    private $gameRepo;
    private $userManager;

    private $autocompletionManager;

    public function setUp(): void
    {
        $this->security = $this->createMock(Security::class);
        $this->steamRepo = $this->createMock(SteamRepository::class);
        $this->gameRepo = $this->createMock(GameRepository::class);
        $this->userManager = $this->createMock(UserManager::class);

        $this->autocompletionManager = new AutocompletionManager(
            self::$autocompletionLimit,
            self::$autocompletionMinLength,
            $this->security,
            $this->steamRepo,
            $this->gameRepo,
            $this->userManager,
        );
    }

    #[PU\Test]
    public function fromSteamTooShort(): void
    {
        $searchMode = SearchModeEnum::LIKE;
        $search = 'yes';

        $this->steamRepo->expects($this->never())->method($searchMode->toRepoMethod());
        $objects = $this->autocompletionManager->fromSteam($search, $searchMode);
        $this->assertSame($objects, []);
    }

    #[PU\Test]
    #[PU\DataProvider('fromValues')]
    public function fromSteamOk(?string $search, SearchModeEnum $searchMode, string $expectedSearch): void
    {
        $steam1 = $this->createMock(Steam::class);
        $steam2 = $this->createMock(Steam::class);

        $this->steamRepo
            ->expects($this->once())
            ->method($searchMode->toRepoMethod())
            ->with($expectedSearch, self::$autocompletionLimit)
            ->willReturn([$steam1, $steam2]);

        $objects = $this->autocompletionManager->fromSteam($search, $searchMode);

        $this->assertSame([$steam1, $steam2], $objects);
    }

    #[PU\Test]
    public function fromGameTooShort(): void
    {
        $searchMode = SearchModeEnum::LIKE;
        $search = 'yes';
        $this->gameRepo->expects($this->never())->method($searchMode->toRepoMethod());

        $objects = $this->autocompletionManager->fromGame($search, $searchMode, true);

        $this->assertSame($objects, []);
    }

    #[PU\Test]
    #[PU\DataProvider('fromValues')]
    public function fromGameOk(?string $search, SearchModeEnum $searchMode, string $expectedSearch): void
    {
        $game1 = $this->createMock(Game::class);
        $game2 = $this->createMock(Game::class);

        $userId = 10;
        $user = $this->createMock(User::class);
        $user->expects($this->once())->method('getId')->willReturn($userId);

        $this->security->expects($this->once())->method('getUser')->willReturn($user);
        $this->gameRepo
            ->expects($this->once())
            ->method($searchMode->toRepoMethod())
            ->with($expectedSearch, self::$autocompletionLimit, $userId)
            ->willReturn([$game1, $game2]);
        $this->userManager->expects($this->once())->method('plugToGamesInfo');
        $this->userManager->expects($this->once())->method('findWithReview');

        $objects = $this->autocompletionManager->fromGame($search, $searchMode, true);

        $this->assertSame([$game1, $game2], $objects);
    }

    #[PU\Test]
    #[PU\DataProvider('sanitizeSearchValues')]
    public function sanitizeSearch(?string $search, SearchModeEnum $searchMode, string $expectedSearch): void
    {
        $this->assertSame($expectedSearch, $this->autocompletionManager->sanitizeSearch($search, $searchMode));
    }

    public static function fromValues(): array
    {
        return [
            'search ok LIKE' => ['welcome', SearchModeEnum::LIKE, '%welcome%'],
            'search ok PATTERN' => ['welcome', SearchModeEnum::PATTERN, '+welcome*'],
        ];
    }

    public static function sanitizeSearchValues(): array
    {
        return [
            'search null' => [null, SearchModeEnum::LIKE, ''],
            'search empty' => ['', SearchModeEnum::LIKE, ''],
            'search too short' => ['yes', SearchModeEnum::LIKE, ''],
            'search too short bis' => [' ye#[s= ', SearchModeEnum::LIKE, ''],
            'search normal LIKE' => ['welcome', SearchModeEnum::LIKE, '%welcome%'],
            'search normal PATTERN' => ['welcome', SearchModeEnum::PATTERN, '+welcome*'],
            'search special LIKE' => [' Welc@Me{ ', SearchModeEnum::LIKE, '%welcme%'],
            'search special PATTERN' => [' Welc@Me{ ', SearchModeEnum::PATTERN, '+welcme*'],
            'search multi LIKE' => [' welcome   to  the moon', SearchModeEnum::LIKE, '%welcome to the moon%'],
            'search multi PATTERN' => [' welcome   to  the moon', SearchModeEnum::PATTERN, '+welcome*+to*+the*+moon*'],
            'search too much word PATTERN' => ['you are welcome to the moon', SearchModeEnum::PATTERN, '+you*+are*+welcome*+to*+the*'],
        ];
    }
}
