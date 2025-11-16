<?php

namespace App\Fixtures\Factory;

use App\Entity\Main\Review;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

final class ReviewFactory extends PersistentProxyObjectFactory
{
    public function __construct()
    {
    }

    public static function class(): string
    {
        return Review::class;
    }

    protected function defaults(): array|callable
    {
        $defaults = [];
        $defaults['dateAdd'] = self::faker()->dateTimeBetween('-6 months', '-2 days');
        $defaults['dateUpdate'] = self::faker()
            ->optional(0.25, $defaults['dateAdd'])
            ->dateTimeBetween($defaults['dateAdd'], '-1 day');
        $defaults['rating'] = self::faker()->randomFloat(1, 0, 6);
        $defaults['hourSpend'] = self::faker()->optional(0.75)->numberBetween(0, 200);
        $defaults['firstPlay'] = self::faker()->optional(0.75)->dateTimeBetween('-25 years', '-1 day');
        $defaults['comment'] = self::faker()->optional(0.75)->paragraph(self::faker()->numberBetween(1, 10));
        $defaults['userId'] = self::faker()->numberBetween(0, 10000);

        return $defaults;
    }

    public function withReleaseYear(int $releaseYear): self
    {
        $releaseDate = new \DateTime($releaseYear . '-12-31');

        return $this->with(function () use ($releaseDate) {
            $defaults = [];
            $defaults['firstPlay'] = self::faker()->optional(0.75)->dateTimeBetween($releaseDate, '-1 day');

            return $defaults;
        });
    }

    public function withGameDateAdd(\DateTime $gameDateAdd): self
    {
        return $this->with(function () use ($gameDateAdd) {
            $defaults = [];
            $defaults['dateAdd'] = self::faker()->dateTimeBetween($gameDateAdd, '-2 days');
            $defaults['dateUpdate'] = self::faker()
                ->optional(0.25, $defaults['dateAdd'])
                ->dateTimeBetween($defaults['dateAdd'], '-1 day');

            return $defaults;
        });
    }

    public function withUserId(int $userId): self
    {
        return $this->with(function () use ($userId) {
            $defaults = [];
            $defaults['userId'] = $userId;

            return $defaults;
        });
    }

    protected function initialize(): static
    {
        return $this;
        // ->afterInstantiate(function(Game $game): void {})
    }
}
