<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\User;
use Mainick\KeycloakClientBundle\Service\Criteria;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Service to retrieve all users from Keycloak.
 */
class KeycloakManager implements KeycloakManagerInterface
{
    public function __construct(private string $clientId, private KeycloakClient $keycloakClient, #[Target('cache.keycloak_manager')] private CacheInterface $cache) {}

    public function getUsersAuthorized(): array
    {
        $makeQueries = function () {
            $clients = $this->keycloakClient->clients()->all('web', new Criteria(['clientId' => $this->clientId]));
            if (1 !== $clients->count()) {
                throw new \RuntimeException('Expect one client, received ' . $clients->count());
            }

            $usersKeycloak = $this->keycloakClient->clients()->getRoleUsers('web', $clients->first()->id, 'USER');
            if (0 === $usersKeycloak->count()) {
                throw new \RuntimeException('No user authorized for this app');
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
