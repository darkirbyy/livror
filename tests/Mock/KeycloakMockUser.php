<?php

declare(strict_types=1);

namespace App\Tests\Mock;

use App\Dto\User;
use Symfony\Component\Security\Core\User\UserInterface;

class KeycloakMockUser implements UserInterface
{
    public function __construct(private User $user) {}

    public function getId(): string
    {
        return $this->user->uuid->toString();
    }

    public function getUserIdentifier(): string
    {
        return $this->user->username;
    }

    public function getUsername(): string
    {
        return $this->user->username;
    }

    public function getRoles(): array
    {
        return ['ROLE_USER'];
    }

    public function eraseCredentials(): void {}
}
