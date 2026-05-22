<?php

declare(strict_types=1);

namespace App\Service;

/**
 * Interface to retrieve all users.
 */
interface KeycloakManagerInterface
{
    public function getUsersAuthorized(): array;
}
