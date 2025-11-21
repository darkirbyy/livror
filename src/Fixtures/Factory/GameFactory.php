<?php

namespace App\Fixtures\Factory;

use App\Entity\Main\Game;
use App\Enum\TypeGameEnum;
use App\Tests\Mock\DataMock;
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
        $defaults['dateUpdate'] = clone $defaults['dateAdd'];
        $defaults['name'] = mb_ucfirst(self::faker()->unique()->words(self::faker()->numberBetween(1, 5), true));
        $defaults['typeGame'] = self::faker()->randomElement(TypeGameEnum::cases());
        $defaults['developers'] = mb_ucfirst(self::faker()->word());
        $defaults['releaseYear'] = self::faker()->numberBetween(1990, date('Y') - 1);
        $defaults['fullPrice'] = self::faker()->randomElement([null, 0, self::faker()->numberBetween(99, 6999)]);
        $defaults['genres'] = implode(', ', array_map('mb_ucfirst', self::faker()->words(self::faker()->numberBetween(1, 6))));
        $defaults['description'] = self::faker()->paragraph(self::faker()->numberBetween(2, 5));
        $defaults['imgUrl'] = 'https://shared.akamai.steamstatic.com/store_item_assets/steam/apps/' . self::faker()->randomElement(DataMock::$steamAppsId) . '/header.jpg';

        return $defaults;
    }

    public function withUsersId(array $usersId): self
    {
        return $this->with(function () use ($usersId) {
            $callback = static fn (int $i) => [
                self::faker()
                    ->unique(1 == $i)
                    ->randomElement($usersId),
            ];

            $defaults = [];
            $defaults['dateAdd'] = self::faker()->dateTimeBetween('-6 months', '-1 day');
            $defaults['dateUpdate'] = clone $defaults['dateAdd'];
            $defaults['releaseYear'] = self::faker()->numberBetween(1990, date('Y') - 1);
            $defaults['reviews'] = ReviewFactory::new()
                ->withReleaseYear($defaults['releaseYear'])
                ->withGameDateAdd($defaults['dateAdd'])
                ->range(0, count($usersId))
                ->applyStateMethod('withUserId', $callback);

            return $defaults;
        });
    }

    protected function initialize(): static
    {
        return $this;
        // ->afterInstantiate(function(Game $game): void {})
    }
}
