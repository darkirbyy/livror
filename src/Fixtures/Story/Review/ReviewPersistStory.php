<?php

namespace App\Fixtures\Story\Review;

use App\Entity\Account\User;
use App\Fixtures\Factory\GameFactory;
use App\Fixtures\Factory\UserFactory;
use App\Fixtures\Story\TestStory;
use Zenstruck\Foundry\Story;

final class ReviewPersistStory extends Story
{
    public function build(): void
    {
        $user1 = TestStory::get('user1');
        $usersId = array_map(fn (User $u) => $u->getId(), UserFactory::all());
        $usersButUser1Id = array_filter($usersId, fn ($id) => $id != $user1->getId());

        // Create 4 games reviewed by user1
        GameFactory::new()
            ->withUsersId([$user1->getId()], true)
            ->many(4)
            ->create();

        // Create 4 games NOT reviewed by user 1
        GameFactory::new()->withUsersId($usersButUser1Id, true)->many(4)->create();
    }
}
