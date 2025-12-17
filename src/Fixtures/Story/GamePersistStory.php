<?php

namespace App\Fixtures\Story;

use App\Fixtures\Factory\GameFactory;
use Zenstruck\Foundry\Story;

final class GamePersistStory extends Story
{
    public function build(): void
    {
        GameFactory::createOne(['steamId' => 2, 'name' => 'Core Keeper']);
        GameFactory::createOne(['steamId' => 5, 'name' => 'Team Fortress Classic']);
    }
}
