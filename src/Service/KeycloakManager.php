<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\User;
use Mainick\KeycloakClientBundle\Interface\IamAdminClientInterface;
use Mainick\KeycloakClientBundle\Service\Criteria;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Service to retrieve all users from Keycloak.
 */
class KeycloakManager implements KeycloakManagerInterface
{
    public function __construct(
        private string $clientId,
        private IamAdminClientInterface $keycloakAdminClient,
        #[Target('cache.keycloak_manager')] private CacheInterface $cache,
    ) {}

    public function getUsersAuthorized(): array
    {
        $makeQueries = function () {
            $clients = $this->keycloakAdminClient->clients()->all('web', new Criteria(['clientId' => $this->clientId]));
            if ($clients->count() !== 1) {
                throw new RuntimeException('Expect one client, received ' . $clients->count());
            }

            $usersKeycloak = $this->keycloakAdminClient->clients()->getRoleUsers('web', $clients->first()->id, 'USER');
            if ($usersKeycloak->count() === 0) {
                throw new RuntimeException('No user authorized for this app');
            }

            $users = [];
            foreach ($usersKeycloak->getIterator() as $userKeycloak) {
                if ($userKeycloak->attributes->contains('picture') && !empty($userKeycloak->attributes->get('picture')[0])) {
                    $avatarPath = $userKeycloak->attributes->get('picture')[0];
                } else {
                    $avatarPath = '';
                }
                $users[$userKeycloak->id] = new User(Uuid::fromString($userKeycloak->id), $userKeycloak->username, $avatarPath);
            }
            return $users;
        };

        return $this->cache->get('users-authorized', $makeQueries, 0);
    }
}
