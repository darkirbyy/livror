<?php

declare(strict_types=1);

namespace App\Tests\Inte\Command;

use App\Fixtures\Story\Command\DiscordNotifyStory;
use PHPUnit\Framework\Attributes as PU;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

final class DiscordNotifyCommandTest extends KernelTestCase
{
    use ResetDatabase;
    use Factories;

    private CommandTester $commandTester;
    private string $discordDir;

    public function setUp(): void
    {
        parent::setUp();

        self::bootKernel();
        $application = new Application(self::$kernel);

        $command = $application->find('app:discord:notify');
        $this->commandTester = new CommandTester($command);

        $this->discordDir = self::getContainer()->getParameter('discord.dir');
    }

    public function tearDown(): void
    {
        $filesystem = static::getContainer()->get(Filesystem::class);
        $filesystem->remove($this->discordDir);

        parent::tearDown();
    }

    #[PU\Test]
    public function noSince(): void
    {
        $this->commandTester->execute([]);
        $this->assertSame(Command::INVALID, $this->commandTester->getStatusCode());
    }

    #[PU\Test]
    public function notifyEmpty(): void
    {
        DiscordNotifyStory::load();

        $this->commandTester->execute([
            '--since' => (new \DateTime())->getTimestamp(),
        ]);

        $output = $this->commandTester->getDisplay();
        $filename = $this->discordDir . '/notify.md';

        $this->assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
        $this->assertFileExists($filename);
        $this->assertStringContainsString('discord.notify.intro', file_get_contents($filename));
        $this->assertStringContainsString('discord.notify.nothingNew', file_get_contents($filename));
        $this->assertSame(3, preg_match_all('/Done/i', $output));
    }

    #[PU\Test]
    public function notifyFull(): void
    {
        DiscordNotifyStory::load();

        $this->commandTester->execute([
            '--since' => (new \DateTime('-1 year'))->getTimestamp(),
        ]);

        $output = $this->commandTester->getDisplay();
        $filename = $this->discordDir . '/notify.md';

        $this->assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
        $this->assertFileExists($filename);
        $this->assertStringContainsString('discord.notify.intro', file_get_contents($filename));
        $this->assertStringContainsString('discord.notify.10PublishedReviewsBy :trophy:', file_get_contents($filename));
        $this->assertStringContainsString('discord.notify.checkOn', file_get_contents($filename));
        $this->assertSame(3, preg_match_all('/Done/i', $output));
    }

    #[PU\Test]
    public function exception(): void
    {
        DiscordNotifyStory::load();

        $apiMockHttpClient = static::getContainer()->get(HttpClientInterface::class);
        $apiMockHttpClient->setOverrideResponse(new MockResponse('', ['http_code' => 400]));

        $this->commandTester->execute([
            '--since' => (new \DateTime())->getTimestamp(),
            '-v' => true,
        ]);

        $output = $this->commandTester->getDisplay();

        $this->assertSame(Command::FAILURE, $this->commandTester->getStatusCode());
        $this->assertStringContainsStringIgnoringCase('Failed', $output);
    }
}
