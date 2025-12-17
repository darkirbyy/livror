<?php

declare(strict_types=1);

namespace App\Tests\Func\Controller;

use App\Entity\Main\Game;
use App\Enum\TypeGameEnum;
use App\Fixtures\Factory\GameFactory;
use App\Fixtures\Story\GameIndexAllStory;
use App\Fixtures\Story\GameIndexStandardStory;
use App\Fixtures\Story\GamePersistStory;
use App\Repository\UserRepository;
use App\Tests\Mock\DataMock;
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
    #[PU\DataProvider('indexValues')]
    public function indexDefault(string $storyClass, string $queryString, array $criteria, array $sortBy, int $expectedNbGames): void
    {
        $storyClass::load();

        $crawler = $this->client->request('GET', '/game?' . $queryString);
        $gamesTitleCrawler = $crawler->filter('div[id^=game] h5');
        $gamesTitle = array_map('trim', $gamesTitleCrawler->extract(['_text']));

        $gameRepo = GameFactory::repository();
        $games = $gameRepo->findBy($criteria, $sortBy);
        $gamesTitleExpected = array_slice(array_map(fn (Game $g) => $g->getName(), $games), 0, $expectedNbGames);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'game.index.title');
        $this->assertSame($gamesTitle, $gamesTitleExpected);
    }

    #[PU\Test]
    #[PU\DataProvider('indexValues')]
    public function indexShowMore(string $storyClass, string $queryString, array $criteria, array $sortBy, int $expectedNbGames): void
    {
        $maxLimit = static::getContainer()->getParameter('app.max_limit');
        $storyClass::load();

        $crawler = $this->client->request('GET', '/game?' . $queryString);
        if ($expectedNbGames == $maxLimit) {
            $this->assertSelectorNotExists('button[data-load-more-target]');

            return;
        }

        $showMoreButton = $crawler->filter('button[data-load-more-target]')->first();
        $xmlUrl = $showMoreButton->ancestors()->first()->attr('data-load-more-url-value');
        $xmlCrawler = $this->client->xmlHttpRequest('GET', $xmlUrl);

        $gamesTitleXmlCrawler = $xmlCrawler->filter('div[id^=game] h5');
        $gamesTitle = array_map('trim', $gamesTitleXmlCrawler->extract(['_text']));

        $gameRepo = GameFactory::repository();
        $games = $gameRepo->findBy($criteria, $sortBy, $maxLimit, $expectedNbGames);
        $gamesTitleExpected = array_slice(array_map(fn (Game $g) => $g->getName(), $games), 0, $expectedNbGames);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorNotExists('h1');
        $this->assertSame($gamesTitle, $gamesTitleExpected);
    }

    #[PU\Test]
    #[PU\DataProvider('newValues')]
    #[WithStory(GamePersistStory::class)]
    public function new(string $queryString, array $expectedGame, array $formOverride, bool $formValid): void
    {
        $crawler = $this->client->request('GET', '/game/new?' . $queryString);

        $form = $crawler->filter('form[name=game]')->form();

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'game.edit.title');
        $this->assertEquals($expectedGame['name'], $form->get('game[name]')->getValue());
        $this->assertEquals($expectedGame['steam_appid'], $form->get('game[steamId]')->getValue());

        $this->client->submit($form, $formOverride);

        if ($formValid) {
            $this->assertResponseRedirects('/game');
        } else {
            $this->assertResponseIsUnprocessable();
        }
    }

    #[PU\Test]
    #[PU\DataProvider('editValues')]
    #[WithStory(GamePersistStory::class)]
    public function edit(string $queryString, array $formOverride, bool $formValid): void
    {
        $gameRepo = GameFactory::repository();
        $game = $gameRepo->first('steamId');
        $crawler = $this->client->request('GET', '/game/' . $game->getId() . '/edit?' . $queryString);

        $form = $crawler->filter('form[name=game]')->form();

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'game.edit.title');
        $this->assertEquals($game->getName(), $form->get('game[name]')->getValue());
        $this->assertEquals($game->getSteamId(), $form->get('game[steamId]')->getValue());

        $this->client->submit($form, $formOverride);

        if ($formValid) {
            $this->assertResponseRedirects('/game');
        } else {
            $this->assertResponseIsUnprocessable();
        }
    }

    #[PU\Test]
    #[PU\DataProvider('deleteValues')]
    #[WithStory(GamePersistStory::class)]
    public function delete(string $tokenName, string $expectedRedirect): void
    {
        $gameRepo = GameFactory::repository();
        $game = $gameRepo->first('steamId');

        $crawler = $this->client->request('GET', '/game/' . $game->getId() . '/edit');
        $tokenValue = $crawler->filter('div[role=dialog] form input[type=hidden]')->attr('value');

        $crawler = $this->client->request('POST', '/game/' . $game->getId() . '/delete', [$tokenName => $tokenValue]);

        $this->assertResponseRedirects($expectedRedirect);
    }

    public static function indexValues(): array
    {
        return [
            'standard' => [
                GameIndexStandardStory::class,
                '',
                ['typeGame' => [TypeGameEnum::GAME, TypeGameEnum::DLC]],
                ['name' => 'ASC'],
                static::getContainer()->getParameter('app.default_limit'),
            ],
            'all' => [GameIndexAllStory::class, 'limit=20&sorts[name]=desc&filters[withoutReview][0]=1', [], ['name' => 'DESC'], 20],
        ];
    }

    public static function newValues(): array
    {
        return [
            'steamId null, valid fields' => ['', ['name' => '', 'steam_appid' => ''], ['game[name]' => 'Half-life 3', 'game[fullPrice]' => 2999], true],
            'steamId null, no name' => ['', ['name' => '', 'steam_appid' => ''], ['game[genres]' => 'Multi'], false],
            'steamId null, duplicate steamId' => ['', ['name' => '', 'steam_appid' => ''], ['game[steamId]' => 1], false],
            'steamId valid, no change' => ['steamId=1', DataMock::$appDetails[1]['data'], [], true],
            'steamId valid, invalid fields' => ['steamId=1', DataMock::$appDetails[1]['data'], ['game[releaseYear]' => 'thousand'], false],
            'steamId valid, duplicate name' => ['steamId=2', DataMock::$appDetails[2]['data'], ['game[name]' => 'Core Keeper'], false],
        ];
    }

    public static function editValues(): array
    {
        return [
            'steamId null, no change' => ['', [], true],
            'steamId null, valid fields' => ['', ['game[name]' => 'Half-life 3', 'game[fullPrice]' => 2999], true],
            'steamId null, invalid fields' => ['', ['game[releaseYear]' => 'thousand'], false],
            'steamId null, duplicate steamId' => ['', ['game[steamId]' => 5], false],
        ];
    }

    public static function deleteValues(): array
    {
        return [
            'valid token' => ['_token', '/game'],
            'wrong token' => ['_game_token', ''],
        ];
    }
}
