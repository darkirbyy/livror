<?php

declare(strict_types=1);

namespace App\Tests\Func\Controller;

use App\Entity\Game;
use App\Entity\Review;
use App\Fixtures\Factory\GameFactory;
use App\Fixtures\Factory\ReviewFactory;
use App\Fixtures\Story\Home\HomeIndexStory;
use PHPUnit\Framework\Attributes as PU;

class HomeControllerTest extends AbstractControllerTest
{
    #[PU\Test]
    public function homeIndex(): void
    {
        HomeIndexStory::load();

        $crawler = $this->client->request('GET', '');

        $sectionsTitleCrawler = $crawler->filter('h4');
        $sectionsTitle = array_map('trim', $sectionsTitleCrawler->extract(['_text']));
        $expectedSectionsTitle = ['home.index.search.title', 'home.index.lastGame.title', 'home.index.lastReview.title'];

        $gamesTitleCrawler = $crawler->filter('div[id^=game] a[id^=title]');
        $gamesTitle = array_map('trim', $gamesTitleCrawler->extract(['_text']));
        $expectedGames = GameFactory::repository()->findBy([], ['dateAdd' => 'DESC', 'id' => 'ASC'], static::getContainer()->getParameter('app.home_game_limit'));
        $expectedGamesTitles = array_map(fn(Game $g) => $g->getName(), $expectedGames);

        $reviewsTitleCrawler = $crawler->filter('div[id^=review] a[id^=title]');
        $reviewsTitle = array_map('trim', $reviewsTitleCrawler->extract(['_text']));
        $expectedReviews = ReviewFactory::repository()->findBy([], ['dateAdd' => 'DESC', 'id' => 'ASC'], static::getContainer()->getParameter('app.home_review_limit'));
        $expectedReviewsTitles = array_map(fn(Review $g) => $g->getGame()->getName(), $expectedReviews);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'home.index.title');
        $this->assertSame($expectedSectionsTitle, $sectionsTitle);
        $this->assertSame($expectedGamesTitles, $gamesTitle);
        $this->assertSame($expectedReviewsTitles, $reviewsTitle);
    }
}
