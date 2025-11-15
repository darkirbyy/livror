<?php

namespace App\Fixtures\Story;

use App\Fixtures\Factory\GameFactory;
use Zenstruck\Foundry\Attribute\AsFixture;
use Zenstruck\Foundry\Story;

#[AsFixture(name: 'default')]
final class DefaultStory extends Story
{
    public function build(): void
    {
        GameFactory::createMany(10);
    }
}
