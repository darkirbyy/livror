<?php

declare(strict_types=1);

namespace App\Tests\Func\Controller;

use App\Entity\Main\Game;
use App\Enum\TypeGameEnum;
use App\Fixtures\Factory\GameFactory;
use App\Fixtures\Story\IndexAllStory;
use App\Fixtures\Story\IndexStandardStory;
use App\Repository\UserRepository;
use App\Tests\Mock\DataMock;
use PHPUnit\Framework\Attributes as PU;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
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
    #[PU\DataProvider('newGetValues')]
    public function newGet(string $queryString, array $expectedGame): void
    {
        $crawler = $this->client->request('GET', '/game/new?' . $queryString);

        $submitButtonCrawler = $crawler->filter('button[type=submit]')->first();
        $form = $submitButtonCrawler->form();

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'game.edit.title');
        $this->assertEquals($expectedGame['name'], $form->get('game[name]')->getValue());
        $this->assertEquals($expectedGame['steam_appid'], $form->get('game[steamId]')->getValue());
    }

    #[PU\Test]
    #[PU\DataProvider('newPostValidValues')]
    public function newPostValid(string $queryString, array $formOverride): void
    {
        $crawler = $this->client->request('GET', '/game/new?' . $queryString);

        $submitButtonCrawler = $crawler->filter('button[type=submit]')->first();
        $form = $submitButtonCrawler->form();

        $this->client->submit($form, $formOverride);

        $this->assertResponseRedirects('/game');
    }

    #[PU\Test]
    #[PU\DataProvider('newPostInvalidValues')]
    public function newPostInvalid(string $queryString, array $formOverride): void
    {
        GameFactory::createOne(['steamId' => 2, 'name' => 'Core Keeper']);

        $crawler = $this->client->request('GET', '/game/new?' . $queryString);

        $submitButtonCrawler = $crawler->filter('button[type=submit]')->first();
        $form = $submitButtonCrawler->form();

        $postCrawler = $this->client->submit($form, $formOverride);

        $this->assertResponseIsUnprocessable();
    }

    public static function indexValues(): array
    {
        return [
            'standard' => [
                IndexStandardStory::class,
                '',
                ['typeGame' => [TypeGameEnum::GAME, TypeGameEnum::DLC]],
                ['name' => 'ASC'],
                static::getContainer()->getParameter('app.default_limit'),
            ],
            'all' => [IndexAllStory::class, 'limit=20&sorts[name]=desc&filters[withoutReview][0]=1', [], ['name' => 'DESC'], 20],
        ];
    }

    public static function newGetValues(): array
    {
        return [
            'steamId null' => ['', ['name' => '', 'steam_appid' => ''], ['game[name]' => 'Half-life 3', 'game[fullPrice]' => 2999]],
            'steamId valid' => ['steamId=1', DataMock::$appDetails[1]['data'], []],
        ];
    }

    public static function newPostValidValues(): array
    {
        return [
            'steamId null' => ['', ['game[name]' => 'Half-life 3', 'game[fullPrice]' => 2999]],
            'steamId valid' => ['steamId=1', []],
        ];
    }

    public static function newPostInvalidValues(): array
    {
        return [
            'steamId null, no name' => ['', ['game[genres]' => 'Multi']],
            'steamId null, duplicate steamId' => ['', ['game[steamId]' => 1]],
            'steamId valid, invalid fields' => ['steamId=1', ['game[releaseYear]' => 'thousand']],
            'steamId valid, duplicate name' => ['steamId=2', ['game[name]' => 'Core Keeper']],
        ];
    }
}
