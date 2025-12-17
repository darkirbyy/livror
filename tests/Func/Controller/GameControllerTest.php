<?php

declare(strict_types=1);

namespace App\Tests\Func\Controller;

use App\Entity\Main\Game;
use App\Enum\TypeGameEnum;
use App\Fixtures\Factory\GameFactory;
use App\Fixtures\Story\IndexAllStory;
use App\Fixtures\Story\IndexDefaultStory;
use App\Repository\UserRepository;
use PHPUnit\Framework\Attributes as PU;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Attribute\WithStory;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class GameControllerTest extends WebTestCase
{
    use ResetDatabase;
    use Factories;

    private KernelBrowser $client;

    public function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();

        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['username' => 'darkirby']);
        $this->client->loginUser($user);
    }

    #[PU\Test]
    #[WithStory(IndexDefaultStory::class)]
    public function indexDefault(): void
    {
        $defaultLimit = static::getContainer()->getParameter('app.default_limit');

        $crawler = $this->client->request('GET', '/game');
        $gamesTitleCrawler = $crawler->filter('div[id^=game] h5');
        $gamesTitle = array_map('trim', $gamesTitleCrawler->extract(['_text']));

        $gameRepo = GameFactory::repository();
        $games = $gameRepo->findBy(['typeGame' => [TypeGameEnum::GAME, TypeGameEnum::DLC]], ['name' => 'ASC'], $defaultLimit);
        $gamesTitleExpected = array_map(fn (Game $g) => $g->getName(), $games);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'game.index.title');
        $this->assertSame($defaultLimit, count($gamesTitleCrawler));
        $this->assertSame($gamesTitle, $gamesTitleExpected);
    }

    #[PU\Test]
    #[WithStory(IndexAllStory::class)]
    public function indexAll(): void
    {
        $defaultLimit = static::getContainer()->getParameter('app.default_limit');

        $crawler = $this->client->request('GET', '/game?filters[withoutReview][0]=1');
        $gamesTitleCrawler = $crawler->filter('div[id^=game] h5');
        $gamesTitle = array_map('trim', $gamesTitleCrawler->extract(['_text']));

        $gameRepo = GameFactory::repository();
        $games = $gameRepo->findBy([], ['name' => 'ASC'], $defaultLimit);
        $gamesTitleExpected = array_map(fn (Game $g) => $g->getName(), $games);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'game.index.title');
        $this->assertSame($defaultLimit, count($gamesTitleCrawler));
        $this->assertSame($gamesTitle, $gamesTitleExpected);
    }
}
