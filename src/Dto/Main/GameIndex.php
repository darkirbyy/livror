<?php

declare(strict_types=1);

namespace App\Dto\Main;

use App\Entity\Main\Game;

class GameIndex
{
    public function __construct(private Game $game, private ?float $avgMark, private ?int $totHourSpend, private ?string $minFirstPlay)
    {
    }

    public function getGame(): Game
    {
        return $this->game;
    }

    public function getAvgMark(): ?float
    {
        return $this->avgMark;
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
