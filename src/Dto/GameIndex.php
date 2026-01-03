<?php

declare(strict_types=1);

namespace App\Dto;

use App\Entity\Main\Game;

class GameIndex
{
    public ?\DateTime $minFirstPlay;

    public function __construct(public Game $game, public ?float $avgRating, public ?int $totHourSpend, ?string $minFirstPlay)
    {
        $this->minFirstPlay = !empty($minFirstPlay) ? new \DateTime($minFirstPlay) : null;
    }

    public function getId(): int
    {
        return $this->game->getId();
    }
}
