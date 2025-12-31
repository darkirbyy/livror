<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Main\Game;
use App\Entity\Main\Steam;
use App\Enum\SearchModeEnum;
use App\Repository\GameRepository;
use App\Repository\SteamRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Environment;

class AutocompletionManager
{
    public function __construct(
        private int $autocompletionLimit,
        private int $autocompletionMinLength,
        private Security $security,
        private SteamRepository $steamRepo,
        private GameRepository $gameRepo,
        private Environment $twig,
    ) {
    }

    /**
     * Search Steam game in the steam table (must have been populated using the command first).
     */
    public function fromSteam(?string $search, SearchModeEnum $searchMode): array
    {
        // Sanitize the user input
        $search = $this->sanitizeSearch($search, $searchMode);
        if (0 == mb_strlen($search)) {
            return [];
        }

        // Query the database and return the data as an array formatted for tomselect
        $repoMethod = $searchMode->toRepoMethod();
        $result = $this->steamRepo->$repoMethod($search, $this->autocompletionLimit);
        $data = array_map(
            fn (Steam $steam) => [
                'value' => $steam->getId(),
                'text' => $this->twig->render('steam/autocomplete.html.twig', ['steam' => $steam]),
            ],
            $result,
        );

        return $data;
    }

    /**
     * Search App game in the game table (excluding game already reviewed by the user if asked).
     */
    public function fromGame(?string $search, SearchModeEnum $searchMode, bool $withoutReview): array
    {
        // Sanitize the user input
        $search = $this->sanitizeSearch($search, $searchMode);
        if (0 == mb_strlen($search)) {
            return [];
        }

        // Query the database and return the data as an array formatted for tomselect
        $userId = $this->security->getUser()->getId();
        $repoMethod = $searchMode->toRepoMethod();
        $result = $this->gameRepo->$repoMethod($search, $this->autocompletionLimit, $withoutReview ? $userId : null);
        $data = array_map(
            fn (Game $game) => [
                'value' => $game->getId(),
                'text' => $this->twig->render('game/autocomplete.html.twig', ['game' => $game]),
            ],
            $result,
        );

        return $data;
    }

    /**
     * Prepare a user input search for mariadb : remove mutliple whitespaces, special characters,
     * enforce min length and lowercase, then add wildcard depending on the search mode.
     *
     * @param string|null    $search     user input search
     * @param SearchModeEnum $searchMode determine the wildcards added
     */
    public function sanitizeSearch(?string $search, SearchModeEnum $searchMode): string
    {
        // Return empty string if empty
        if (empty($search)) {
            return '';
        }

        // Remove multiple whitespaces
        $search = trim(preg_replace('/\s+/', ' ', $search));

        // Remove special characters that can interfer with mariadb fulltext search
        $search = preg_replace('/[^\p{L}\p{N}\s\-]/u', '', $search);

        // Lower all remaining characters
        $search = mb_strtolower($search);

        // Return empty string if too short
        if (mb_strlen($search) < $this->autocompletionMinLength) {
            return '';
        }

        if (SearchModeEnum::PATTERN == $searchMode) {
            // Identify each word (max 5) and surround each with + and * for mariadb fulltext boolean mode
            $words = array_slice(array_filter(explode(' ', $search), fn ($word) => strlen($word) > 0), 0, 5);
            $search = implode('', array_map(fn ($word) => '+' . $word . '*', $words));
        } else {
            // Add % wildcard for mariadb like clause
            $search = '%' . $search . '%';
        }

        return $search;
    }
}
