<?php

namespace App\Fixtures\Story;

use App\Fixtures\Factory\UserFactory;
use Zenstruck\Foundry\Story;

final class TestStory extends Story
{
    public function build(): void
    {
        // Create 4 users named user1, user2, etc,
        $this->addState('connected-user', UserFactory::createOne(['username' => 'user1']));
        $this->addToPool(
            'other-users',
            UserFactory::new()
                ->sequence(array_map(fn($i) => ['username' => 'user' . $i], range(2, 4)))
                ->create(),
        );
    }
}
