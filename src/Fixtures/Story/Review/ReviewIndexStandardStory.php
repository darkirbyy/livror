<?php

namespace App\Fixtures\Story\Review;

use App\Entity\Account\User;
use App\Fixtures\Factory\GameFactory;
use App\Fixtures\Factory\UserFactory;
use Zenstruck\Foundry\Story;

final class ReviewIndexStandardStory extends Story
{
    public function build(): void
    {
        $user1 = UserFactory::repository()->findOneBy(['username' => 'user1']);
        $usersId = array_map(fn (User $u) => $u->getId(), UserFactory::all());
        $usersButUser1Id = array_filter($usersId, fn ($id) => $id != $user1->getId());

        // Create 20 games reviewed by user1
        GameFactory::new()
            ->withUsersId([$user1->getId()], true)
            ->many(20)
            ->create();

        // Create 10 games NOT reviewed by user 1, with "welcome" in the name
        GameFactory::new()->withUsersId($usersButUser1Id, false)->many(10)->create();
    }
}
