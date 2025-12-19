<?php

declare(strict_types=1);

namespace App\Tests\Func\Controller;

use App\Entity\Main\Game;
use App\Entity\Main\Review;
use App\Fixtures\Factory\ReviewFactory;
use App\Fixtures\Story\Review\ReviewIndexAllStory;
use App\Fixtures\Story\Review\ReviewIndexStandardStory;
use App\Fixtures\Story\Review\ReviewPersistStory;
use App\Fixtures\Story\TestStory;
use PHPUnit\Framework\Attributes as PU;

#[PU\RequiresFunction('databaseAvailable')]
class ReviewControllerTest extends AbstractControllerTest
{
    #[PU\Test]
    #[PU\DataProvider('indexValues')]
    public function indexDefault(string $storyClass, string $queryString, int $expectedNbGames, bool $canAdd): void
    {
        $storyClass::load();

        $user1 = TestStory::get('user1');
        $reviews = ReviewFactory::repository()->findBy(['userId' => $user1->getId()]);
        usort($reviews, fn (Review $r1, Review $r2) => $r1->getGame()->getName() <=> $r2->getGame()->getName());
        $gamesTitleExpected = array_slice(array_map(fn (Review $r) => $r->getGame()->getName(), $reviews), 0, $expectedNbGames);

        $crawler = $this->client->request('GET', '/review?' . $queryString);

        $gamesTitleCrawler = $crawler->filter('div[id^=review] h5');
        $gamesTitle = array_map('trim', $gamesTitleCrawler->extract(['_text']));

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'review.index.title');
        $this->assertSame($gamesTitle, $gamesTitleExpected);

        if ($canAdd) {
            $this->assertSelectorNotExists('div[class~=alert-secondary]');
        } else {
            $this->assertSelectorTextContains('div[class~=alert-secondary]', 'review.index.flash.cannotAddReview');
        }
    }

    #[PU\Test]
    #[PU\DataProvider('indexValues')]
    public function indexShowMore(string $storyClass, string $queryString, int $expectedNbGames, bool $canAdd): void
    {
        $storyClass::load();
        $maxLimit = static::getContainer()->getParameter('app.max_limit');

        $crawler = $this->client->request('GET', '/review?' . $queryString);

        if ($expectedNbGames == $maxLimit) {
            $this->assertSelectorNotExists('button[data-load-more-target]');

            return;
        }

        $user1 = TestStory::get('user1');
        $reviews = ReviewFactory::repository()->findBy(['userId' => $user1->getId()]);
        usort($reviews, fn (Review $r1, Review $r2) => $r1->getGame()->getName() <=> $r2->getGame()->getName());
        $gamesTitleExpected = array_slice(array_map(fn (Review $r) => $r->getGame()->getName(), $reviews), $expectedNbGames, $expectedNbGames);

        $showMoreButton = $crawler->filter('button[data-load-more-target]')->first();
        $xmlUrl = $showMoreButton->ancestors()->first()->attr('data-load-more-url-value');
        $xmlCrawler = $this->client->xmlHttpRequest('GET', $xmlUrl);

        $gamesTitleXmlCrawler = $xmlCrawler->filter('div[id^=review] h5');
        $gamesTitle = array_map('trim', $gamesTitleXmlCrawler->extract(['_text']));

        $this->assertResponseIsSuccessful();
        $this->assertSelectorNotExists('h1');
        $this->assertSame($gamesTitle, $gamesTitleExpected);
    }

    // #[PU\Test]
    // #[PU\DataProvider('newAvailableValues')]
    // public function newAvailable(bool $prefill, bool $overrideGame, bool $formValid): void
    // {
    //     ReviewPersistStory::load();
    //     $previousCount = ReviewFactory::repository()->count();
    //     $gameNotCommented = ReviewPersistStory::get('notCommented');

    //     $crawler = $this->client->request('GET', '/review/new?' . $prefill ? 'gameId=' . $gameNotCommented->getId() : '');
    //     $form = $crawler->filter('form[name=review]')->form();

    //     $this->assertResponseIsSuccessful();
    //     $this->assertSelectorTextContains('h1', 'review.edit.title');
    //     if ($prefill) {
    //         $this->assertEquals($gameNotCommented->getName(), $form->get('review[game]')->getValue());
    //     }

    //     $formOverride = [];
    //     $overrideGame ? ($formOverride['review[game]'] = ReviewPersistStory::get('notCommented')->getId()) : null;
    //     $formOverride['review[rating]'] = 5;
    //     $this->client->submit($form, $formOverride);

    //     if ($formValid) {
    //         ReviewFactory::assert()->count($previousCount + 1);
    //         $this->assertResponseRedirects('/review');
    //     } else {
    //         ReviewFactory::assert()->count($previousCount);
    //         $this->assertResponseIsUnprocessable();
    //     }
    // }

    #[PU\Test]
    #[PU\DataProvider('editValues')]
    public function edit(array $formOverride, bool $formValid): void
    {
        ReviewPersistStory::load();

        $previousCount = ReviewFactory::repository()->count();
        $review = ReviewFactory::repository()->findOneBy(['userId' => TestStory::get('user1')->getId()]);

        $crawler = $this->client->request('GET', '/review/' . $review->getId() . '/edit');
        $form = $crawler->filter('form[name=review]')->form();

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'review.edit.title');
        $this->assertEquals(false, $form->has('review[game]'));
        $this->assertEquals($review->getRating(), $form->get('review[rating]')->getValue());

        $this->client->submit($form, $formOverride);

        ReviewFactory::assert()->count($previousCount);
        if ($formValid) {
            $this->assertResponseRedirects('/review');
        } else {
            $this->assertResponseIsUnprocessable();
        }
    }

    #[PU\Test]
    #[PU\DataProvider('deleteValues')]
    public function delete(bool $validToken, string $expectedRedirect): void
    {
        ReviewPersistStory::load();

        $previousCount = ReviewFactory::repository()->count();
        $review = ReviewFactory::repository()->findOneBy(['userId' => TestStory::get('user1')->getId()]);

        $crawler = $this->client->request('GET', '/review/' . $review->getId() . '/edit');
        $tokenValue = $validToken ? $crawler->filter('div[role=dialog] form input[type=hidden]')->attr('value') : '';

        $crawler = $this->client->request('POST', '/review/' . $review->getId() . '/delete', ['_token' => $tokenValue]);
        if ($validToken) {
            ReviewFactory::assert()->count($previousCount - 1);
        } else {
            ReviewFactory::assert()->count($previousCount);
        }
        $this->assertResponseRedirects($expectedRedirect);
    }

    public static function indexValues(): array
    {
        return [
            'standard' => [ReviewIndexStandardStory::class, '', static::getContainer()->getParameter('app.default_limit'), true],
            'all' => [ReviewIndexAllStory::class, 'limit=20', 20, false],
        ];
    }

    // public static function newAvailableValues(): array
    // {
    //     return [
    //         'gameId null, no game' => [true, false, false],
    //         'gameId null, valid game' => ['', '', true, true],
    //         // 'gameId valid' => ['gameId=', [],true],
    //     ];
    // }

    public static function editValues(): array
    {
        return [
            'valid fields' => [[], true],
            'invalid fields' => [['review[rating]' => 7], false],
        ];
    }

    public static function deleteValues(): array
    {
        return [
            'wrong token' => [false, ''],
            'valid token' => [true, '/review'],
        ];
    }
}
