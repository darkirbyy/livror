<?php

namespace App\Fixtures\Story;

use App\Fixtures\Factory\UserFactory;
use Zenstruck\Foundry\Story;

final class TestStory extends Story
{
    public function build(): void
    {
        // Create 4 users named user1, user2, etc, the first being set into a state
        $this->addState('user1', UserFactory::createOne(['username' => 'user1']));
        UserFactory::new()
            ->sequence(array_map(fn ($i) => ['username' => 'user' . $i], range(2, 4)))
            ->create();
    }
}
