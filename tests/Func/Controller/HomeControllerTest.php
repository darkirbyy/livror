<?php

declare(strict_types=1);

namespace App\Tests\Func\Controller;

use App\Entity\Main\Game;
use App\Entity\Main\Review;
use App\Fixtures\Factory\GameFactory;
use App\Fixtures\Factory\ReviewFactory;
use App\Fixtures\Story\Home\HomeIndexStory;
use App\Service\HubUrlGenerator;
use PHPUnit\Framework\Attributes as PU;

class HomeControllerTest extends AbstractControllerTest
{
    #[PU\Test]
    public function homeNotLoggedIn(): void
    {
        // disconnect the user by removing the session cookie
        $cookieJar = $this->client->getCookieJar();
        $cookieJar->clear();

        $hubUrlGenerator = static::getContainer()->get(HubUrlGenerator::class);
        $expectedUrl = $hubUrlGenerator->generateAccount('/login');

        $this->client->request('GET', '');

        $this->assertResponseRedirects($expectedUrl);
    }

    #[PU\Test]
    public function homeLoggedIn(): void
    {
        $this->client->request('GET', '');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'home.index.title');
    }

    #[PU\Test]
    public function homeIndex(): void
    {
        HomeIndexStory::load();

        $crawler = $this->client->request('GET', '');

        $sectionsTitleCrawler = $crawler->filter('h4');
        $sectionsTitle = array_map('trim', $sectionsTitleCrawler->extract(['_text']));
        $expectedSectionsTitle = ['home.index.search.title', 'home.index.lastGame.title', 'home.index.lastReview.title'];

        $gamesTitleCrawler = $crawler->filter('div[id^=game] h5');
        $gamesTitle = array_map('trim', $gamesTitleCrawler->extract(['_text']));
        $expectedGames = GameFactory::repository()->findBy([], ['dateAdd' => 'DESC', 'id' => 'ASC'], static::getContainer()->getParameter('app.home_game_limit'));
        $expectedGamesTitles = array_map(fn (Game $g) => $g->getName(), $expectedGames);

        $reviewsTitleCrawler = $crawler->filter('div[id^=review] h5');
        $reviewsTitle = array_map('trim', $reviewsTitleCrawler->extract(['_text']));
        $expectedReviews = ReviewFactory::repository()->findBy([], ['dateAdd' => 'DESC', 'id' => 'ASC'], static::getContainer()->getParameter('app.home_review_limit'));
        $expectedReviewsTitles = array_map(fn (Review $g) => $g->getGame()->getName(), $expectedReviews);

        $this->assertResponseIsSuccessful();
        $this->assertSame($expectedSectionsTitle, $sectionsTitle);
        $this->assertSame($expectedGamesTitles, $gamesTitle);
        $this->assertSame($expectedReviewsTitles, $reviewsTitle);
    }

    #[PU\Test]
    public function account(): void
    {
        $hubUrlGenerator = static::getContainer()->get(HubUrlGenerator::class);
        $expectedUrl = $hubUrlGenerator->generateAccount('');

        $this->client->request('GET', '/account');
        $session = $this->client->getRequest()->getSession();

        $this->assertResponseRedirects($expectedUrl);
        $this->assertTrue($session->has('hub/back-target-path'));
    }
}
