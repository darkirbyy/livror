<?php

namespace App\Fixtures\Factory;

use App\Entity\Account\User;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

final class UserFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return User::class;
    }

    protected function defaults(): array
    {
        return [
            'username' => 'user',
            'password' => self::faker()->sha1(),
            'roles' => ['ROLE_USER'],
            'avatarPath' => self::faker()->url(),
        ];
    }
}
