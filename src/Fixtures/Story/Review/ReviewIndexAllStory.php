<?php

namespace App\Fixtures\Story\Review;

use App\Fixtures\Factory\GameFactory;
use App\Fixtures\Story\TestStory;
use Zenstruck\Foundry\Story;

final class ReviewIndexAllStory extends Story
{
    public function build(): void
    {
        $user1 = TestStory::get('connected-user');

        // Create 20 games reviewed by user1
        GameFactory::new()
            ->withUsersId([$user1->getId()], true)
            ->many(20)
            ->create();
    }
}
