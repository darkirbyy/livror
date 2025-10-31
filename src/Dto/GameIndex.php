<?php

declare(strict_types=1);

namespace App\Dto;

use App\Entity\Main\Game;

class GameIndex
{
    public function __construct(private Game $game, private ?float $avgRating, private ?int $totHourSpend, private ?string $minFirstPlay)
    {
    }

    public function getGame(): Game
    {
        return $this->game;
    }

    public function getAvgRating(): ?float
    {
        return $this->avgRating;
    }

    public function getTotHourSpend(): ?int
    {
        return $this->totHourSpend;
    }

    public function getMinFirstPlay(): ?\DateTime
    {
        return $this->minFirstPlay ? new \DateTime($this->minFirstPlay) : null;
    }
}
