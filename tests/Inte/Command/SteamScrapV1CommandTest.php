<?php

declare(strict_types=1);

namespace App\Tests\Inte\Command;

use App\Entity\Main\Steam;
use App\Tests\Mock\DataMock;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes as PU;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

final class SteamScrapV1CommandTest extends KernelTestCase
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

        $command = $application->find('steam:scrap:v1');
        $this->commandTester = new CommandTester($command);

        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->tableName = $this->entityManager->getClassMetadata(Steam::class)->getTableName();
    }

    #[PU\Test]
    public function truncate(): void
    {
        $this->commandTester->execute([]);

        $steamNb = $this->entityManager->getConnection()->fetchOne('SELECT COUNT(*) FROM ' . $this->tableName);
        $output = $this->commandTester->getDisplay();

        $this->assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
        $this->assertSame(count(DataMock::$appsListTruncate), $steamNb);
        $this->assertStringContainsStringIgnoringCase('Deleting existing data', $output);
        $this->assertStringContainsStringIgnoringCase('Deleting existing data', $output);
        $this->assertSame(6, preg_match_all('/Done/i', $output));
    }
}
