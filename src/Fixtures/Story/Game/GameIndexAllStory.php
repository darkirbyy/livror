<?php

namespace App\Fixtures\Story\Game;

use App\Fixtures\Factory\GameFactory;
use App\Fixtures\Story\TestStory;
use Zenstruck\Foundry\Story;

final class GameIndexAllStory extends Story
{
    public function build(): void
    {
        // Create 20 games with "0" to "number of users" reviews
        $allUsersUuid = TestStory::getPool('all-users-uuid');
        GameFactory::new()->withUsersUuid($allUsersUuid, false)->many(20)->create();
    }
}
