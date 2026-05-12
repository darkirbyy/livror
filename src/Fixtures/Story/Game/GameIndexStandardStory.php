<?php

namespace App\Fixtures\Story\Game;

use App\Enum\TypeGameEnum;
use App\Fixtures\Factory\GameFactory;
use App\Fixtures\Story\TestStory;
use Zenstruck\Foundry\Story;

final class GameIndexStandardStory extends Story
{
    public function build(): void
    {
        // Create 17 games with "1" to "number of users" reviews, only of type of game GAME and DLC
        $allUsersUuid = TestStory::getPool('all-users-uuid');
        GameFactory::new()
            ->withUsersUuid($allUsersUuid, true)
            ->withTypesGame([TypeGameEnum::GAME, TypeGameEnum::DLC])
            ->many(17)
            ->create();
    }
}
