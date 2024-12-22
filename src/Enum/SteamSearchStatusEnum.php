<?php

declare(strict_types=1);

namespace App\Enum;

enum SteamSearchStatusEnum: string
{
    case PENDING = 'pending';
    case OK = 'ok';
    case INVALID_ID = 'invalid_id';
    case ERROR = 'error';
}
