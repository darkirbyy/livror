<?php

declare(strict_types=1);

namespace App\Tests\Func\Controller;

use App\Fixtures\Story\TestStory;
use App\Tests\Mock\KeycloakMockUser;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

abstract class AbstractControllerTest extends WebTestCase
{
    use ResetDatabase;
    use Factories;

    protected KernelBrowser $client;

    public function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();

        $user = new KeycloakMockUser(TestStory::get('connected-user'));
        $this->client->loginUser($user);
    }

    public function tearDown(): void
    {
        $filesystem = static::getContainer()->get(Filesystem::class);
        $vichMappings = static::getContainer()->getParameter('vich_uploader.mappings');
        $filesystem->remove($vichMappings['attachments']['upload_destination']);

        parent::tearDown();
    }
}
