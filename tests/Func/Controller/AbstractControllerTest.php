<?php

declare(strict_types=1);

namespace App\Tests\Func\Controller;

use App\Fixtures\Story\TestStory;
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

        $this->client->loginUser(TestStory::get('connected-user'));
    }

    public function tearDown(): void
    {
        $filesystem = static::getContainer()->get(Filesystem::class);
        $vichMappings = static::getContainer()->getParameter('vich_uploader.mappings');
        $filesystem->remove($vichMappings['attachments']['upload_destination']);

        parent::tearDown();
    }
}
