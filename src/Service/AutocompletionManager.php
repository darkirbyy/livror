<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\GameRepository;
use App\Repository\SteamRepository;
use Symfony\Bundle\SecurityBundle\Security;

class AutocompletionManager
{
    public function __construct(
        private int $autocompletionLimit,
        private int $autocompletionMinLength,
        private Security $security,
        private SteamRepository $steamRepo,
        private GameRepository $gameRepo,
    ) {
    }

    public function fromSteam(?string $search): mixed
    {
        $pattern = $this->sanitizeSearch($search);

        return strlen($pattern) >= $this->autocompletionMinLength ? $this->steamRepo->findPattern($pattern, $this->autocompletionLimit) : [];
    }

    public function fromGame(?string $search): array
    {
        $pattern = $this->sanitizeSearch($search);
        $userId = $this->security->getUser()->getId();

        return strlen($pattern) >= $this->autocompletionMinLength ? $this->gameRepo->findPatternWithoutReview($userId, $pattern, $this->autocompletionLimit) : [];
    }

    public function sanitizeSearch(?string $search): string
    {
        // Return empty string if empty
        if (empty($search)) {
            return '';
        }

        // Remove multiple whitespaces
        $search = trim(preg_replace('/\s+/', ' ', $search));

        // Remove special characters that can interfer with mariadb fulltext search
        $search = preg_replace('/[^\p{L}\p{N}\s\-]/u', ' ', $search);

        // Identify each word and keep maximum 5 of them
        $words = array_slice(array_filter(explode(' ', $search), fn ($word) => strlen($word) > 0), 0, 5);

        // Surround each word with + and * for mariadb db fulltext boolean mode
        $pattern = implode('', array_map(fn ($word) => '+' . $word . '*', $words));

        return $pattern;
    }
}
