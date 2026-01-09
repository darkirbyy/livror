<?php

declare(strict_types=1);

namespace App\Tests\Inte\Command;

use App\Entity\Main\Steam;
use App\Tests\Mock\ApiMockData;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes as PU;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

final class SteamScrapV2CommandTest extends KernelTestCase
{
    use ResetDatabase;
    use Factories;

    private $commandTester;
    private $entityManager;
    private $tableName;

    public function setUp(): void
    {
        parent::setUp();

        self::bootKernel();
        $application = new Application(self::$kernel);

        $command = $application->find('steam:scrap:v2');
        $this->commandTester = new CommandTester($command);

        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->tableName = $this->entityManager->getClassMetadata(Steam::class)->getTableName();
    }

    #[PU\Test]
    #[PU\DataProvider('invalidInputsValues')]
    public function invalidInputs(array $inputs): void
    {
        $this->commandTester->execute($inputs);
        $this->assertSame(Command::INVALID, $this->commandTester->getStatusCode());
    }

    #[PU\Test]
    public function truncate(): void
    {
        $this->commandTester->execute([
            'mode' => 'truncate',
        ]);

        $steamNb = $this->entityManager->getConnection()->fetchOne('SELECT COUNT(*) FROM ' . $this->tableName);
        $output = $this->commandTester->getDisplay();

        $this->assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
        $this->assertSame(count(ApiMockData::$appsListTruncate), $steamNb);
        $this->assertStringContainsStringIgnoringCase('Deleting existing data', $output);
        $this->assertSame(5, preg_match_all('/Done/i', $output));
    }

    #[PU\Test]
    #[PU\DataProvider('updateValues')]
    public function update(int $since, $expectedSteamNb): void
    {
        $this->commandTester->execute([
            'mode' => 'update',
            '--since' => $since,
        ]);

        $steamNb = $this->entityManager->getConnection()->fetchOne('SELECT COUNT(*) FROM ' . $this->tableName);
        $output = $this->commandTester->getDisplay();

        $this->assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
        $this->assertSame($expectedSteamNb, $steamNb);
        $this->assertStringNotContainsStringIgnoringCase('Deleting existing data', $output);
        $this->assertSame(4, preg_match_all('/Done/i', $output));
    }

    #[PU\Test]
    public function exception(): void
    {
        $apiMockHttpClient = static::getContainer()->get(HttpClientInterface::class);
        $apiMockHttpClient->setOverrideResponse(new MockResponse('', ['error' => 'exception']));

        $this->commandTester->execute([
            'mode' => 'truncate',
            '-v' => true,
        ]);

        $steamNb = $this->entityManager->getConnection()->fetchOne('SELECT COUNT(*) FROM ' . $this->tableName);
        $output = $this->commandTester->getDisplay();

        $this->assertSame(Command::FAILURE, $this->commandTester->getStatusCode());
        $this->assertStringContainsStringIgnoringCase('Failed', $output);
        $this->assertSame(0, $steamNb);
    }

    public static function invalidInputsValues(): array
    {
        return [
            'invalid mode' => [['mode' => 'reset']],
            'no since with update mode' => [['mode' => 'update']],
        ];
    }

    public static function updateValues(): array
    {
        return [
            'all games' => [0, 8],
            'some games' => [50, 6],
            'no games' => [1000, 0],
        ];
    }
}
