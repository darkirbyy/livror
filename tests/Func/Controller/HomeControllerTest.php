<?php

declare(strict_types=1);

namespace App\Tests\Func\Controller;

use App\Repository\UserRepository;
use App\Service\HubUrlGenerator;
use PHPUnit\Framework\Attributes as PU;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class HomeControllerTest extends WebTestCase
{
    use ResetDatabase;
    use Factories;

    private KernelBrowser $client;

    public function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
    }

    #[PU\Test]
    public function homeNotLoggedIn(): void
    {
        $hubUrlGenerator = static::getContainer()->get(HubUrlGenerator::class);
        $expectedUrl = $hubUrlGenerator->generateAccount('/login');

        $crawler = $this->client->request('GET', '');

        $this->assertResponseRedirects($expectedUrl);
    }

    #[PU\Test]
    public function homeLoggedIn(): void
    {
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['username' => 'user1']);
        $this->client->loginUser($user);

        $crawler = $this->client->request('GET', '');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'home.index.title');
    }

    #[PU\Test]
    public function account(): void
    {
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['username' => 'user1']);
        $this->client->loginUser($user);

        $hubUrlGenerator = static::getContainer()->get(HubUrlGenerator::class);
        $expectedUrl = $hubUrlGenerator->generateAccount('');

        $crawler = $this->client->request('GET', '/account');
        $session = $this->client->getRequest()->getSession();

        $this->assertResponseRedirects($expectedUrl);
        $this->assertTrue($session->has('hub/back-target-path'));
    }
}
