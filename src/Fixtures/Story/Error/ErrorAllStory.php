<?php

namespace App\Fixtures\Story\Error;

use App\Entity\Account\User;
use App\Fixtures\Factory\GameFactory;
use App\Fixtures\Factory\SteamFactory;
use App\Fixtures\Factory\UserFactory;
use App\Fixtures\Story\TestStory;
use Zenstruck\Foundry\Story;

final class ErrorAllStory extends Story
{
    public function build(): void
    {
        $usersUuid = TestStory::getPool('all-users-uuid');
        GameFactory::new()->withUsersUuid($usersUuid, false, 'forced')->many(10)->create();
        SteamFactory::new()
            ->sequence(array_map(fn($i) => ['id' => $i], range(1, 50)))
            ->create();
    }
}
