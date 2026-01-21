<?php

namespace App\Fixtures\Story\Game;

use App\Entity\Account\User;
use App\Fixtures\Factory\GameFactory;
use App\Fixtures\Factory\UserFactory;
use Zenstruck\Foundry\Story;

final class GameIndexAllStory extends Story
{
    public function build(): void
    {
        // Create 20 games with "0" to "number of users" reviews
        $usersId = array_map(fn(User $u) => $u->getId(), UserFactory::all());
        GameFactory::new()->withUsersId($usersId, false)->many(20)->create();
    }
}
