<?php

declare(strict_types=1);

namespace App\Enum;

enum SearchModeEnum: string
{
    case PATTERN = 'PATTERN';
    case LIKE = 'LIKE';

    // Convert to the repo method name
    public function toRepoMethod(): string
    {
        return match ($this) {
            self::PATTERN => 'findPattern',
            self::LIKE => 'findLike',
        };
    }
}
