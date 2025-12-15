<?php

declare(strict_types=1);

namespace App\Tests\Func\Controller;

use App\Repository\UserRepository;
use PHPUnit\Framework\Attributes as PU;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class GameControllerTest extends WebTestCase
{
    private $client;

    public function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();

        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['username' => 'darkirby']);
        $this->client->loginUser($user);
    }

    #[PU\Test]
    public function index(): void
    {
        $crawler = $this->client->request('GET', '/game');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'game.index.title');
    }
}
