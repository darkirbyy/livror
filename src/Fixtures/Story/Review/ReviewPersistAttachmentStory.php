<?php

namespace App\Fixtures\Story\Review;

use App\Fixtures\Factory\GameFactory;
use App\Fixtures\Story\TestStory;
use Zenstruck\Foundry\Story;

final class ReviewPersistAttachmentStory extends Story
{
    public function build(): void
    {
        $connectedUserUuid = TestStory::get('connected-user-uuid');

        // Create 4 games reviewed by user1 with one attachment each
        GameFactory::new()
            ->withUsersUuid([$connectedUserUuid], true, 'forced')
            ->many(4)
            ->create();
    }
}
