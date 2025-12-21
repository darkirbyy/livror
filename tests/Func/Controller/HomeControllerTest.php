<?php

declare(strict_types=1);

namespace App\Tests\Func\Controller;

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
