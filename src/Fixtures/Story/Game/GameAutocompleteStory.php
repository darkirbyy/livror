<?php

namespace App\Fixtures\Story\Game;

use App\Entity\Account\User;
use App\Fixtures\Factory\GameFactory;
use App\Fixtures\Story\TestStory;
use Zenstruck\Foundry\Story;

final class GameAutocompleteStory extends Story
{
    public function build(): void
    {
        $user1 = TestStory::get('connected-user');
        $usersButUser1Id = array_map(fn(User $u) => $u->getId(), TestStory::getPool('other-users'));

        // Create 10 games NOT reviewed by user 1, with "welcome" in the name
        GameFactory::new()
            ->withUsersId($usersButUser1Id, false)
            ->sequence(array_map(fn($i) => ['name' => 'welcome ' . $i], range(1, 10)))
            ->create();

        // Create 2 games reviewed by user 1, with "goodbye" in the name
        GameFactory::new()
            ->withUsersId([$user1->getId()], true)
            ->sequence(array_map(fn($i) => ['name' => 'goodbye ' . $i], range(1, 2)))
            ->create();

        // Create 2 games NOT reviewed by user 1, with "goodbye" in the name
        GameFactory::new()
            ->withUsersId($usersButUser1Id, false)
            ->sequence(array_map(fn($i) => ['name' => 'goodbye ' . $i], range(3, 4)))
            ->create();
    }
}
