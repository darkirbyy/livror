<?php

declare(strict_types=1);

namespace App\Service;

use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\DBAL\Exception\NotNullConstraintViolationException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Exception;
use Psr\Log\LoggerInterface;

/**
 * Service to handle exceptions when they are catched (to display + logging).
 */
class ExceptionManager
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    /**
     * Log catched exception to keep trace.
     */
    public function handle(string $method, string $message, array $params = []): void
    {
        $this->logger->$method($message, $params);
    }

    /**
     * Transform a constraint exception into a user message.
     */
    public function handleDatabase(\Exception $exception): string
    {
        $this->handle('warning', $exception->getMessage());
        if ($exception instanceof ForeignKeyConstraintViolationException) {
            $message = 'form.flash.foreignKey';
        } elseif ($exception instanceof NotNullConstraintViolationException) {
            $message = 'form.flash.notNull';
        } elseif ($exception instanceof UniqueConstraintViolationException) {
            $message = 'form.flash.unique';
        } else {
            $message = 'form.flash.other';
        }

        return $message;
    }
}
