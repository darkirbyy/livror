<?php

namespace App\Fixtures\Story\Game;

use App\Fixtures\Factory\GameFactory;
use App\Fixtures\Story\TestStory;
use Zenstruck\Foundry\Story;

final class GameAutocompleteStory extends Story
{
    public function build(): void
    {
        $connectedUserUuid = TestStory::get('connected-user-uuid');
        $otherUsersUuid = TestStory::getPool('other-users-uuid');

        // Create 10 games NOT reviewed by user 1, with "welcome" in the name
        GameFactory::new()->withUsersUuid($otherUsersUuid, false)->sequence(array_map(fn($i) => ['name' => 'welcome ' . $i], range(1, 10)))->create();

        // Create 2 games reviewed by user 1, with "goodbye" in the name
        GameFactory::new()
            ->withUsersUuid([$connectedUserUuid], true)
            ->sequence(array_map(fn($i) => ['name' => 'goodbye ' . $i], range(1, 2)))
            ->create();

        // Create 2 games NOT reviewed by user 1, with "goodbye" in the name
        GameFactory::new()->withUsersUuid($otherUsersUuid, false)->sequence(array_map(fn($i) => ['name' => 'goodbye ' . $i], range(3, 4)))->create();
    }
}
