<?php

declare(strict_types=1);

namespace App\Enum;

enum SteamSearchStatusEnum: string
{
    case PENDING = 'PENDING';
    case OK = 'OK';
    case NOT_FOUND = 'NOT_FOUND';
    case ERROR = 'ERROR';
}
