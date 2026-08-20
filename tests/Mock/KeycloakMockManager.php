<?php

declare(strict_types=1);

namespace App\Tests\Mock;

use App\Dto\User;
use App\Service\KeycloakManager;
use App\Service\KeycloakManagerInterface;
use Symfony\Component\Asset\Packages;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Uid\UuidV4;

/**
 * Service to create test users.
 */
class KeycloakMockManager implements KeycloakManagerInterface
{
    public function __construct(private KeycloakManager $inner, private ParameterBagInterface $parameterBag, private Packages $packages) {}

    #[\Override]
    public function getUsersAuthorized(): array
    {
        if ($this->parameterBag->get('app.mock_keycloak')) {
            $userList = [];
            foreach (range(1, 4) as $i) {
                $user = $this->createUser($i);
                $userList[$user->uuid->toString()] = $user;
            }

            return $userList;
        }

        return $this->inner->getUsersAuthorized();
    }

    public function createUser(int $i)
    {
        if ($i < 1 || $i > 4) {
            throw new \ValueError('Dummy user $i must be between 1 and 4, ' . $i . ' given');
        }
        $uuid = UuidV4::fromString('11111111-1111-4111-8111-' . 111111111111 * $i);
        $username = 'user' . $i;
        $avatarPath = $this->packages->getUrl('build/tests/avatar' . $i . '.png');

        return new User($uuid, $username, $avatarPath);
    }
}
