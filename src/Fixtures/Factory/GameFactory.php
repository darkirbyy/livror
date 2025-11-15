<?php

namespace App\Fixtures\Factory;

use App\Entity\Main\Game;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

final class GameFactory extends PersistentProxyObjectFactory
{
    public function __construct()
    {
    }

    public static function class(): string
    {
        return Game::class;
    }

    protected function defaults(): array|callable
    {
        $defaults = [];
        $defaults['dateAdd'] = self::faker()->dateTimeBetween('-6 months', '-1 day');
        $defaults['dateUpdate'] = $defaults['dateAdd'];
        $defaults['name'] = self::faker()->text(80);
        $defaults['developers'] = mb_ucfirst(self::faker()->word());
        $defaults['releaseYear'] = self::faker()->numberBetween(1990, 2025);
        $defaults['fullPrice'] = self::faker()->randomElement([null, 0, self::faker()->numberBetween(99, 6999)]);
        $defaults['genres'] = implode(', ', array_map('mb_ucfirst', self::faker()->words(self::faker()->numberBetween(1, 6))));
        $defaults['description'] = self::faker()->paragraph(self::faker()->numberBetween(2, 5));

        return $defaults;
    }

    protected function initialize(): static
    {
        return $this;
        // ->afterInstantiate(function(Game $game): void {})
    }
}
