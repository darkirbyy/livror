<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\ExceptionManager;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\DBAL\Exception\NotNullConstraintViolationException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use PHPUnit\Framework\Attributes as PU;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

#[PU\AllowMockObjectsWithoutExpectations]
final class ExceptionManagerTest extends TestCase
{
    #[PU\Test]
    #[PU\DataProvider('handleValues')]
    public function handleLogging(string $method, string $message, array $params): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method($method);

        $exceptionManager = new ExceptionManager($logger);
        $exceptionManager->handle($method, $message, $params);
    }

    #[PU\Test]
    #[PU\DataProvider('handleDatabaseValues')]
    public function handleDatatabseLogging(\Exception $exception, string $transKey): void
    {
        $expectedMessage = 'form.flash.' . $transKey;
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('warning');

        $exceptionManager = new ExceptionManager($logger);
        $this->assertSame($expectedMessage, $exceptionManager->handleDatabase($exception));
    }

    public static function handleValues(): array
    {
        return [
            'info' => ['info', 'message', []],
            'warning' => ['warning', 'message', []],
            'error and one param' => ['error', 'message with {param}', ['param' => 'value1']],
            'critical and two params' => ['critical', 'message with {param1} and {param2}', ['param1' => 'value1', 'param2' => 'value2']],
        ];
    }

    public static function handleDatabaseValues(): array
    {
        // Faux TheDriverException compatible avec la signature
        $fakeDriver = new class extends \Exception implements \Doctrine\DBAL\Driver\Exception {
            public function getSQLState(): ?string
            {
                return null;
            }
        };

        $driverException = new DriverException($fakeDriver, null);

        return [
            'runtime exception' => [new \RuntimeException('runtime error'), 'other'],
            'file exception' => [new FileException('file error'), 'other'],
            'not null constraint' => [new NotNullConstraintViolationException($driverException, null), 'notNull'],
            'foreign key constraint' => [new ForeignKeyConstraintViolationException($driverException, null), 'foreignKey'],
            'unique constraint' => [new UniqueConstraintViolationException($driverException, null), 'unique'],
        ];
    }
}
