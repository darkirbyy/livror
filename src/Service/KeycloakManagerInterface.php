<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\User;

/**
 * Interface to retrieve all users.
 */
interface KeycloakManagerInterface
{
   public function getUsersAuthorized(): array;
}
