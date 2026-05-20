<?php

declare(strict_types=1);

namespace App\Tests\Inte\Command;

use App\Fixtures\Factory\AttachmentFactory;
use App\Fixtures\Factory\ReviewFactory;
use App\Fixtures\Story\Command\DeleteReviewsStory;
use App\Fixtures\Story\TestStory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes as PU;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

final class DeleteReviewsCommandTest extends KernelTestCase
{
    use ResetDatabase;
    use Factories;

    private CommandTester $commandTester;

    public function setUp(): void
    {
        parent::setUp();

        self::bootKernel();
        $application = new Application(self::$kernel);

        $command = $application->find('app:delete:reviews');
        $this->commandTester = new CommandTester($command);
    }

    public function tearDown(): void {}

    #[PU\Test]
    #[PU\DataProvider('invalidInputsValues')]
    public function invalidInputs(array $inputs): void
    {
        $this->commandTester->execute($inputs);
        $this->assertSame(Command::INVALID, $this->commandTester->getStatusCode());
    }

    #[PU\Test]
    public function noReviews(): void
    {
        DeleteReviewsStory::load();

        $this->commandTester->execute([
            'userUuid' => TestStory::getRandom('other-users-uuid')->toString(),
        ]);
        $output = $this->commandTester->getDisplay();

        $this->assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
        $this->assertStringContainsStringIgnoringCase('Canceled', $output);
    }

    #[PU\Test]
    public function cancelDeletion(): void
    {
        DeleteReviewsStory::load();

        $this->commandTester->setInputs(['no']);
        $this->commandTester->execute([
            'userUuid' => TestStory::get('connected-user-uuid')->toString(),
        ]);
        $output = $this->commandTester->getDisplay();

        $this->assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
        $this->assertStringContainsStringIgnoringCase('Canceled', $output);
        ReviewFactory::assert()->count(5);
        AttachmentFactory::assert()->count(5);
    }

    #[PU\Test]
    public function confirmDeletion(): void
    {
        DeleteReviewsStory::load();
        $userUuid = TestStory::get('connected-user-uuid')->toString();

        $this->commandTester->setInputs(['yes']);
        $this->commandTester->execute([
            'userUuid' => $userUuid,
        ]);
        $output = $this->commandTester->getDisplay();

        $this->assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
        $this->assertStringContainsStringIgnoringCase('Done', $output);
        ReviewFactory::assert()->count(0);
        AttachmentFactory::assert()->count(0);
    }

    #[PU\Test]
    public function exception(): void
    {
        DeleteReviewsStory::load();
        $userUuid = TestStory::get('connected-user-uuid')->toString();

        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $entityManager->close();

        $this->commandTester->setInputs(['yes']);
        $this->commandTester->execute([
            'userUuid' => $userUuid,
            '-v' => true,
        ]);
        $output = $this->commandTester->getDisplay();

        $this->assertSame(Command::FAILURE, $this->commandTester->getStatusCode());
        $this->assertStringContainsStringIgnoringCase('Failed', $output);
        ReviewFactory::assert()->count(5);
        AttachmentFactory::assert()->count(5);
    }

    public static function invalidInputsValues(): array
    {
        return [
            'id instead of uuid' => [['userUuid' => '123']],
            'wrong uuid format' => [['userUuid' => '0000-0000-0000-0000-000000000000']],
        ];
    }
}
