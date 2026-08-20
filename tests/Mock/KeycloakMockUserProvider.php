<?php

declare(strict_types=1);

namespace App\Tests\Mock;

use App\Service\KeycloakManagerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class KeycloakMockUserProvider implements UserProviderInterface
{
    public function __construct(private KeycloakManagerInterface $keycloakManager) {}

    #[\Override]
    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        return new KeycloakMockUser($this->keycloakManager->createUser((int) $identifier));
    }

    #[\Override]
    public function refreshUser(UserInterface $user): UserInterface
    {
        return new KeycloakMockUser($this->keycloakManager->createUser(1));
    }

    #[\Override]
    public function supportsClass(string $class): bool
    {
        return KeycloakMockUser::class == $class;
    }
}
