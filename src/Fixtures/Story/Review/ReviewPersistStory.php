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
        $connectedUserUuid = TestStory::get('connected-user-uuid');
        $otherUsersUuid = TestStory::getPool('other-users-uuid');

        // Create 4 games reviewed by user1
        GameFactory::new()
            ->withUsersUuid([$connectedUserUuid], true)
            ->many(4)
            ->create();

        // Create 4 games NOT reviewed by user 1, one being set in a state
        $this->addState('notCommented', GameFactory::new()->withUsersUuid($otherUsersUuid, false)->create());
        GameFactory::new()->withUsersUuid($otherUsersUuid, true)->many(3)->create();
    }
}
