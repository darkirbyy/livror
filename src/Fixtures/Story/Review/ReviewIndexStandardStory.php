<?php

namespace App\Fixtures\Story\Review;

use App\Entity\Account\User;
use App\Fixtures\Factory\GameFactory;
use App\Fixtures\Story\TestStory;
use Zenstruck\Foundry\Story;

final class ReviewIndexStandardStory extends Story
{
    public function build(): void
    {
        $connectedUserUuid = TestStory::get('connected-user-uuid');
        $otherUsersUuid = TestStory::getPool('other-users-uuid');

        // Create 12 games reviewed by user1
        GameFactory::new()
            ->withUsersUuid([$connectedUserUuid], true)
            ->many(12)
            ->create();

        // Create 10 games NOT reviewed by user 1
        GameFactory::new()->withUsersUuid($otherUsersUuid, true)->many(10)->create();
    }
}
