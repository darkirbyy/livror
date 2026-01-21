<?php

namespace App\Fixtures\Story\Review;

use App\Entity\Account\User;
use App\Fixtures\Factory\GameFactory;
use App\Fixtures\Story\TestStory;
use Zenstruck\Foundry\Story;

final class ReviewPersistStory extends Story
{
    public function build(): void
    {
        $user1 = TestStory::get('connected-user');
        $usersButUser1Id = array_map(fn(User $u) => $u->getId(), TestStory::getPool('other-users'));

        // Create 4 games reviewed by user1
        GameFactory::new()
            ->withUsersId([$user1->getId()], true)
            ->many(4)
            ->create();

        // Create 4 games NOT reviewed by user 1, one being set in a state
        $this->addState('notCommented', GameFactory::new()->withUsersId($usersButUser1Id, false)->create());
        GameFactory::new()->withUsersId($usersButUser1Id, true)->many(3)->create();
    }
}
