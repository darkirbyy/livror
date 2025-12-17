<?php

namespace App\Fixtures\Story;

use App\Entity\Account\User;
use App\Enum\TypeGameEnum;
use App\Fixtures\Factory\GameFactory;
use App\Fixtures\Factory\UserFactory;
use Zenstruck\Foundry\Story;

final class GameIndexStandardStory extends Story
{
    public function build(): void
    {
        // Create 20 games with "1" to "number of users" reviews, only of type of game GAME and DLC
        $usersId = array_map(fn (User $u) => $u->getId(), UserFactory::all());
        GameFactory::new()
            ->withUsersId($usersId, true)
            ->withTypesGame([TypeGameEnum::GAME, TypeGameEnum::DLC])
            ->many(20)
            ->create();
    }
}
