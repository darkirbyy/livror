<?php

declare(strict_types=1);

namespace App\Tests\Func\Controller;

use App\Entity\Review;
use App\Fixtures\Factory\AttachmentFactory;
use App\Fixtures\Factory\ReviewFactory;
use App\Fixtures\Story\Review\ReviewIndexAllStory;
use App\Fixtures\Story\Review\ReviewIndexStandardStory;
use App\Fixtures\Story\Review\ReviewPersistAttachmentStory;
use App\Fixtures\Story\Review\ReviewPersistStory;
use App\Fixtures\Story\TestStory;
use PHPUnit\Framework\Attributes as PU;
use Symfony\Component\DomCrawler\Crawler;

class ReviewControllerTest extends AbstractControllerTest
{
    #[PU\Test]
    #[PU\DataProvider('indexValues')]
    public function indexDefault(string $storyClass, string $queryString, int $expectedNbGames, bool $canAdd): void
    {
        $storyClass::load();

        $connectedUserUuid = TestStory::get('connected-user-uuid');
        $reviews = ReviewFactory::repository()->findBy(['userUuid' => $connectedUserUuid]);
        usort($reviews, fn(Review $r1, Review $r2) => $r1->getGame()->getName() <=> $r2->getGame()->getName());
        $gamesTitleExpected = array_slice(array_map(fn(Review $r) => $r->getGame()->getName(), $reviews), 0, $expectedNbGames);

        $crawler = $this->client->request('GET', '/review?' . $queryString);

        $gamesTitleCrawler = $crawler->filter('a[id^=title]');
        $gamesTitle = array_map('trim', $gamesTitleCrawler->extract(['_text']));

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'review.index.title.owner');
        $this->assertSame($gamesTitleExpected, $gamesTitle);
        $this->assertAnySelectorTextContains('div a.btn.btn-primary', 'review.index.button.addReview');

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

        $connectedUserUuid = TestStory::get('connected-user-uuid');
        $reviews = ReviewFactory::repository()->findBy(['userUuid' => $connectedUserUuid]);
        usort($reviews, fn(Review $r1, Review $r2) => $r1->getGame()->getName() <=> $r2->getGame()->getName());
        $gamesTitleExpected = array_slice(array_map(fn(Review $r) => $r->getGame()->getName(), $reviews), $expectedNbGames, $expectedNbGames);

        $showMoreButton = $crawler->filter('button[data-load-more-target]')->first();
        $xmlUrl = $showMoreButton->ancestors()->first()->attr('data-load-more-url-value');
        $xmlCrawler = $this->client->xmlHttpRequest('GET', $xmlUrl);

        $gamesTitleXmlCrawler = $xmlCrawler->filter('a[id^=title]');
        $gamesTitle = array_map('trim', $gamesTitleXmlCrawler->extract(['_text']));

        $this->assertResponseIsSuccessful();
        $this->assertSelectorNotExists('h1');
        $this->assertSame($gamesTitleExpected, $gamesTitle);
    }

    #[PU\Test]
    #[PU\DataProvider('indexValues')]
    public function indexOtherUser(string $storyClass, string $queryString, int $expectedNbGames, bool $canAdd): void
    {
        $storyClass::load();

        $otherUserUuid = TestStory::getRandom('other-users-uuid');
        $reviews = ReviewFactory::repository()->findBy(['userUuid' => $otherUserUuid]);
        usort($reviews, fn(Review $r1, Review $r2) => $r1->getGame()->getName() <=> $r2->getGame()->getName());
        $gamesTitleExpected = array_slice(array_map(fn(Review $r) => $r->getGame()->getName(), $reviews), 0, $expectedNbGames);

        $crawler = $this->client->request('GET', '/review/' . $otherUserUuid . '?' . $queryString);

        $gamesTitleCrawler = $crawler->filter('a[id^=title]');
        $gamesTitle = array_map('trim', $gamesTitleCrawler->extract(['_text']));

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'review.index.title.other');
        $this->assertSame($gamesTitleExpected, $gamesTitle);
        $this->assertAnySelectorTextContains('div a.btn.btn-outline-light', 'review.index.button.seeMyReviews');
        $this->assertSelectorNotExists('div[class~=alert-secondary]');
    }

    #[PU\Test]
    #[PU\DataProvider('newValues')]
    public function new(bool $prefill, array $formOverride, bool $formValid): void
    {
        ReviewPersistStory::load();
        $previousCount = ReviewFactory::repository()->count();
        $game = ReviewPersistStory::get('notCommented');

        $crawler = $this->client->request('GET', '/review/new' . ($prefill ? '?gameId=' . $game->getId() : ''));
        $form = $crawler->filter('form[name=review]')->form();

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'review.edit.title');
        $prefill ? $this->assertEquals($game->getId(), $form->get('review[game]')->getValue()) : null;

        $this->client->submit($form, array_merge(['review[rating]' => 3], $formOverride));

        if ($formValid) {
            ReviewFactory::assert()->count($previousCount + 1);
            $this->assertResponseRedirects('/review');
        } else {
            ReviewFactory::assert()->count($previousCount);
            $this->assertResponseIsUnprocessable();
        }
    }

    #[PU\Test]
    public function newNotAvailable(): void
    {
        ReviewIndexAllStory::load();

        $this->expectException(\RuntimeException::class);
        $this->client->catchExceptions(false);

        $this->client->request('GET', '/review/new');
    }

    #[PU\Test]
    #[PU\DataProvider('editValues')]
    public function edit(array $formOverride, bool $formValid): void
    {
        ReviewPersistStory::load();

        $connectedUserUuid = TestStory::get('connected-user-uuid');
        $previousCount = ReviewFactory::repository()->count();
        $review = ReviewFactory::repository()->findOneBy(['userUuid' => $connectedUserUuid]);

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
    public function addAttachment(): void
    {
        ReviewPersistAttachmentStory::load();

        $connectedUserUuid = TestStory::get('connected-user-uuid');
        $previousCount = AttachmentFactory::repository()->count();
        $review = ReviewFactory::repository()->findOneBy(['userUuid' => $connectedUserUuid]);

        $crawler = $this->client->request('GET', '/review/' . $review->getId() . '/edit');

        // Parsing the dom to find the prototype and add it to the dom like the javascript does
        $newFieldHtml = str_replace('__name__', '1', $crawler->filter('form div[data-prototype]')->attr('data-prototype'));
        $formNode = $crawler->filter('form[name=review]')->getNode(0);
        $newFieldNode = $formNode->ownerDocument->createDocumentFragment();
        $newFieldNode->appendXML($newFieldHtml);
        $formNode->appendChild($newFieldNode);

        // Create a new crawler with this new dom to extract the form
        $newCrawler = new Crawler($crawler->getNode(0), $crawler->getUri(), $crawler->getBaseHref());
        $form = $newCrawler->filter('form[name=review]')->form();

        $this->assertResponseIsSuccessful();

        $tempFile = tempnam(sys_get_temp_dir(), 'new-');
        file_put_contents($tempFile, 'new content');

        $form->offsetSet('review[attachments][1][file][file]', $tempFile);
        $form->offsetSet('review[attachments][1][description]', 'new description');

        $this->client->submit($form);

        AttachmentFactory::assert()->count($previousCount + 1);
        $this->assertResponseRedirects('/review');
    }

    #[PU\Test]
    public function editAttachment(): void
    {
        ReviewPersistAttachmentStory::load();

        $connectedUserUuid = TestStory::get('connected-user-uuid');
        $previousCount = AttachmentFactory::repository()->count();
        $review = ReviewFactory::repository()->findOneBy(['userUuid' => $connectedUserUuid]);

        $crawler = $this->client->request('GET', '/review/' . $review->getId() . '/edit');
        $form = $crawler->filter('form[name=review]')->form();

        $this->assertResponseIsSuccessful();

        $tempFile = tempnam(sys_get_temp_dir(), 'edit-');
        file_put_contents($tempFile, 'edit content');

        $form->offsetSet('review[attachments][0][file][file]', $tempFile);
        $form->offsetSet('review[attachments][0][description]', 'new description');

        $this->client->submit($form);

        AttachmentFactory::assert()->count($previousCount);
        $this->assertResponseRedirects('/review');
    }

    #[PU\Test]
    public function removeAttachment(): void
    {
        ReviewPersistAttachmentStory::load();

        $connectedUserUuid = TestStory::get('connected-user-uuid');
        $previousCount = AttachmentFactory::repository()->count();
        $review = ReviewFactory::repository()->findOneBy(['userUuid' => $connectedUserUuid]);

        $crawler = $this->client->request('GET', '/review/' . $review->getId() . '/edit');
        $form = $crawler->filter('form[name=review]')->form();

        $this->assertResponseIsSuccessful();

        $form->offsetUnset('review[attachments][0]');

        $this->client->submit($form);

        AttachmentFactory::assert()->count($previousCount - 1);
        $this->assertEmpty($review->getAttachments());
        $this->assertResponseRedirects('/review');
    }

    #[PU\Test]
    #[PU\DataProvider('deleteValues')]
    public function delete(bool $validToken, string $expectedRedirect): void
    {
        ReviewPersistStory::load();

        $connectedUserUuid = TestStory::get('connected-user-uuid');
        $previousCount = ReviewFactory::repository()->count();
        $review = ReviewFactory::repository()->findOneBy(['userUuid' => $connectedUserUuid]);

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

    public static function newValues(): array
    {
        return [
            'empty, invalid fields' => [false, ['review[firstPlay]' => 'thousand'], false],
            'prefill, invalid fields' => [true, ['review[hourSpend]' => 'hundred'], false],
            'empty, valid fields' => [false, ['review[hourSpend]' => 150], true],
            'prefill, valid fields' => [true, [], true],
        ];
    }

    public static function editValues(): array
    {
        return [
            'invalid fields' => [['review[rating]' => 7], false],
            'no change' => [[], true],
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
