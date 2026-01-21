<?php

declare(strict_types=1);

namespace App\Tests\Func\Controller;

use App\Entity\Account\User;
use App\Fixtures\Factory\ReviewFactory;
use App\Fixtures\Story\Error\ErrorAllStory;
use App\Fixtures\Story\TestStory;
use App\Service\HubUrlGenerator;
use PHPUnit\Framework\Attributes as PU;

class ErrorControllerTest extends AbstractControllerTest
{
    #[PU\Test]
    public function notLoggedIn(): void
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
    #[PU\DataProvider('error404Values')]
    public function error404(string $method, string $route): void
    {
        ErrorAllStory::load();

        $this->client->request($method, $route);

        $this->assertResponseStatusCodeSame(404);
        $this->assertSelectorTextContains('main p', 'error.message.notFound');
    }

    #[PU\Test]
    #[PU\DataProvider('error403Values')]
    public function error403(string $method, string $route): void
    {
        ErrorAllStory::load();
        $review = ReviewFactory::repository()->findOneBy(['userId' => array_map(fn(User $u) => $u->getId(), TestStory::getPool('other-users'))]);

        $this->client->request($method, '/review/' . $review->getId() . $route);

        $this->assertResponseStatusCodeSame(403);
        $this->assertSelectorTextContains('main p', 'error.message.forbidden');
    }

    #[PU\Test]
    public function errorOther(): void
    {
        $this->client->request('GET', '/error/500');

        $this->assertResponseStatusCodeSame(500);
        $this->assertSelectorTextContains('main p', 'error.message.other');
    }

    public static function error404Values(): array
    {
        return [
            'no route' => ['GET', '/not-a-route'],
            'no game show' => ['GET', '/game/1500'],
            'no game edit' => ['GET', '/game/1500/edit'],
            'no game delete' => ['POST', '/game/1500/delete'],
            'no review edit' => ['GET', '/review/1500/edit'],
        ];
    }

    public static function error403Values(): array
    {
        return [
            'review edit' => ['GET', '/edit'],
            'review post' => ['POST', '/edit'],
            'review post' => ['POST', '/delete'],
        ];
    }
}
