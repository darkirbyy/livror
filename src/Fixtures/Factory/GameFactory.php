<?php

namespace App\Fixtures\Factory;

use App\Entity\Main\Game;
use App\Enum\TypeGameEnum;
use App\Tests\Mock\ApiMockData;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

final class GameFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return Game::class;
    }

    protected function defaults(): array|callable
    {
        $defaults = [];
        $defaults['dateAdd'] = self::faker()->dateTimeBetween('-6 months', '-3 days');
        $defaults['dateUpdate'] = clone $defaults['dateAdd'];
        $defaults['name'] = mb_ucfirst(
            self::faker()
                ->unique()
                ->words(self::faker()->numberBetween(1, 5), true),
        );
        $defaults['typeGame'] = self::faker()->randomElement(TypeGameEnum::cases());
        $defaults['developers'] = mb_ucfirst(self::faker()->word());
        $defaults['releaseYear'] = self::faker()->numberBetween(1990, date('Y') - 1);
        $defaults['fullPrice'] = self::faker()->randomElement([null, 0, self::faker()->numberBetween(99, 6999)]);
        $defaults['genres'] = implode(', ', array_map('mb_ucfirst', self::faker()->words(self::faker()->numberBetween(1, 6))));
        $defaults['description'] = self::faker()->paragraph(self::faker()->numberBetween(2, 5));
        $defaults['imgUrl'] = 'https://shared.akamai.steamstatic.com/store_item_assets/steam/apps/' . self::faker()->randomElement(ApiMockData::$appsId) . '/header.jpg';

        return $defaults;
    }

    public function withUsersId(array $usersId, bool $atLeastOne): self
    {
        return $this->with(function () use ($usersId, $atLeastOne) {
            $users = self::faker()->randomElements($usersId, self::faker()->numberBetween($atLeastOne ? 1 : 0, count($usersId)), false);

            $defaults = [];
            $defaults['dateAdd'] = self::faker()->dateTimeBetween('-6 months', '-3 days');
            $defaults['dateUpdate'] = clone $defaults['dateAdd'];
            $defaults['releaseYear'] = self::faker()->numberBetween(1990, date('Y') - 1);
            $defaults['reviews'] = ReviewFactory::new()
                ->withReleaseYear($defaults['releaseYear'])
                ->withGameDateAdd($defaults['dateAdd'])
                ->sequence(array_map(fn ($userId) => ['userId' => $userId], $users));

            return $defaults;
        });
    }

    public function withTypesGame(array $typesGame): self
    {
        return $this->with(function () use ($typesGame) {
            $defaults = [];
            $defaults['typeGame'] = self::faker()->randomElement($typesGame);

            return $defaults;
        });
    }
}
