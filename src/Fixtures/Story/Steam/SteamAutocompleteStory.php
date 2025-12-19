<?php

namespace App\Fixtures\Story\Steam;

use App\Fixtures\Factory\SteamFactory;
use Zenstruck\Foundry\Story;

final class SteamAutocompleteStory extends Story
{
    public function build(): void
    {
        // Create 10 steam with "welcome" in the name
        SteamFactory::new()
            ->sequence(array_map(fn ($i) => ['id' => $i, 'name' => 'welcome ' . $i], range(1, 10)))
            ->create();

        // Create 2 steam with "goodbye" in the name
        SteamFactory::new()
            ->sequence(array_map(fn ($i) => ['id' => $i, 'name' => 'goodbye ' . $i], range(11, 12)))
            ->create();

        // Create 2 steam with "maybe" in the name
        SteamFactory::new()
            ->sequence(array_map(fn ($i) => ['id' => $i, 'name' => 'goodies ' . $i], range(13, 14)))
            ->create();
    }
}
