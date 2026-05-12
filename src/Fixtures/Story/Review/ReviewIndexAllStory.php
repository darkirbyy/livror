<?php

namespace App\Fixtures\Story\Review;

use App\Fixtures\Factory\GameFactory;
use App\Fixtures\Story\TestStory;
use Zenstruck\Foundry\Story;

final class ReviewIndexAllStory extends Story
{
    public function build(): void
    {
        $connectedUserUuid = TestStory::get('connected-user-uuid');

        // Create 20 games reviewed by user1
        GameFactory::new()
            ->withUsersUuid([$connectedUserUuid], true)
            ->many(20)
            ->create();
    }
}
