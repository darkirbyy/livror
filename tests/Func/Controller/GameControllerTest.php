<?php

declare(strict_types=1);

namespace App\Tests\Func\Controller;

use App\Entity\Main\Game;
use App\Enum\TypeGameEnum;
use App\Fixtures\Factory\GameFactory;
use App\Fixtures\Story\Game\GameAutocompleteStory;
use App\Fixtures\Story\Game\GameIndexAllStory;
use App\Fixtures\Story\Game\GameIndexStandardStory;
use App\Fixtures\Story\Game\GamePersistStory;
use App\Tests\Mock\DataMock;
use PHPUnit\Framework\Attributes as PU;

#[PU\RequiresFunction('databaseAvailable')]
class GameControllerTest extends AbstractControllerTest
{
    #[PU\Test]
    #[PU\DataProvider('indexValues')]
    public function indexDefault(string $storyClass, string $queryString, array $criteria, array $sortBy, int $expectedNbGames): void
    {
        $storyClass::load();
        $gameRepo = GameFactory::repository();
        $games = $gameRepo->findBy($criteria, $sortBy);
        $gamesTitleExpected = array_slice(array_map(fn (Game $g) => $g->getName(), $games), 0, $expectedNbGames);

        $crawler = $this->client->request('GET', '/game?' . $queryString);

        $gamesTitleCrawler = $crawler->filter('div[id^=game] h5');
        $gamesTitle = array_map('trim', $gamesTitleCrawler->extract(['_text']));

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'game.index.title');
        $this->assertSame($gamesTitle, $gamesTitleExpected);
    }

    #[PU\Test]
    #[PU\DataProvider('indexValues')]
    public function indexShowMore(string $storyClass, string $queryString, array $criteria, array $sortBy, int $expectedNbGames): void
    {
        $storyClass::load();
        $maxLimit = static::getContainer()->getParameter('app.max_limit');

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
    public function new(string $queryString, array $expectedGame, array $formOverride, bool $formValid): void
    {
        GamePersistStory::load();
        $gameRepo = GameFactory::repository();
        $previousCount = $gameRepo->count();

        $crawler = $this->client->request('GET', '/game/new?' . $queryString);
        $form = $crawler->filter('form[name=game]')->form();

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'game.edit.title');
        $this->assertEquals($expectedGame['name'], $form->get('game[name]')->getValue());
        $this->assertEquals($expectedGame['steam_appid'], $form->get('game[steamId]')->getValue());

        $this->client->submit($form, $formOverride);

        if ($formValid) {
            GameFactory::assert()->count($previousCount + 1);
            $this->assertResponseRedirects('/game');
        } else {
            GameFactory::assert()->count($previousCount);
            $this->assertResponseIsUnprocessable();
        }
    }

    #[PU\Test]
    #[PU\DataProvider('editValues')]
    public function edit(string $queryString, array $formOverride, bool $formValid): void
    {
        GamePersistStory::load();
        $gameRepo = GameFactory::repository();
        $previousCount = $gameRepo->count();
        $game = $gameRepo->first('steamId');

        $crawler = $this->client->request('GET', '/game/' . $game->getId() . '/edit?' . $queryString);
        $form = $crawler->filter('form[name=game]')->form();

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'game.edit.title');
        $this->assertEquals($game->getName(), $form->get('game[name]')->getValue());
        $this->assertEquals($game->getSteamId(), $form->get('game[steamId]')->getValue());

        $this->client->submit($form, $formOverride);

        GameFactory::assert()->count($previousCount);
        if ($formValid) {
            $this->assertResponseRedirects('/game');
        } else {
            $this->assertResponseIsUnprocessable();
        }
    }

    #[PU\Test]
    #[PU\DataProvider('deleteValues')]
    public function delete(bool $validToken, string $expectedRedirect): void
    {
        GamePersistStory::load();
        $gameRepo = GameFactory::repository();
        $previousCount = $gameRepo->count();
        $game = $gameRepo->first('steamId');

        $crawler = $this->client->request('GET', '/game/' . $game->getId() . '/edit');
        $tokenValue = $validToken ? $crawler->filter('div[role=dialog] form input[type=hidden]')->attr('value') : '';

        $crawler = $this->client->request('POST', '/game/' . $game->getId() . '/delete', ['_token' => $tokenValue]);
        if ($validToken) {
            GameFactory::assert()->count($previousCount - 1);
        } else {
            GameFactory::assert()->count($previousCount);
        }
        $this->assertResponseRedirects($expectedRedirect);
    }

    #[PU\Test]
    #[PU\DataProvider('autocompleteValues')]
    public function autocomplete(string $queryString, int $expectedNbGames): void
    {
        GameAutocompleteStory::load();

        $this->client->request('GET', '/game/autocomplete?' . $queryString);

        $response = $this->client->getResponse();
        $content = $response->getContent();
        $contentArray = json_decode($content, true);

        $this->assertResponseIsSuccessful();
        $this->assertJson($content);
        $this->assertArrayHasKey('results', $contentArray);
        $this->assertSame($expectedNbGames, count($contentArray['results']));
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
            'valid token' => [true, '/game'],
            'wrong token' => [false, ''],
        ];
    }

    public static function autocompleteValues(): array
    {
        return [
            'valid, max results' => ['query=welcome', static::getContainer()->getParameter('app.autocompletion_limit')],
            'valid, 2 results' => ['query=goodbye', 2],
        ];
    }
}
