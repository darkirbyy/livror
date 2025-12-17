<?php

namespace App\Fixtures\Story;

use App\Fixtures\Factory\UserFactory;
use Zenstruck\Foundry\Story;

final class TestStory extends Story
{
    public function build(): void
    {
        // Create 4 users named user1, user2, etc
        UserFactory::new()
            ->sequence(array_map(fn ($i) => ['username' => 'user' . $i], range(1, 4)))
            ->create();
    }
}
