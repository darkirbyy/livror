<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\User;
use LogicException;
use Mainick\KeycloakClientBundle\Provider\KeycloakAdminClient;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Service to retrieve all users from Keycloak.
 */
class KeycloakManager implements KeycloakManagerInterface
{
   public function __construct(
      //private  KeycloakAdminClient $keycloakAdminClient
   ) {}

   public function getUsersAuthorized(): array
   {
      // todo : implement
      throw new \Exception('Not implemented');
   }
}
