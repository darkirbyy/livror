<?php

declare(strict_types=1);

namespace App\Enum;

enum SteamSearchStatusEnum: string
{
    case PENDING = 'pending';
    case OK = 'ok';
    case NOT_FOUND = 'not_found';
    case ERROR = 'error';
}
