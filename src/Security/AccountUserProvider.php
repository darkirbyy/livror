<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\Account\User;
use App\Repository\Account\UserRepository;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class AccountUserProvider implements UserProviderInterface
{
    public function __construct(private UserRepository $userRepo)
    {
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $user = $this->userRepo->find((int) $identifier);

        if (!$user) {
            throw new UserNotFoundException(sprintf('User "%s" not found.', $identifier));
        }

        return $user;
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Invalid user class "%s".', get_class($user)));
        }

        $refreshedUser = $this->userRepo->find($user->getId());

        if (!$refreshedUser) {
            throw new UserNotFoundException('User not found.');
        }

        return $refreshedUser;
    }

    public function supportsClass(string $class): bool
    {
        return User::class === $class || is_subclass_of($class, User::class);
    }
}
