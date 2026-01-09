<?php

declare(strict_types=1);

namespace App\Enum;

enum DateFieldEnum: string
{
    case ADD = 'ADD';
    case UPDATE = 'UPDATE';

    // Convert to the database field name
    public function toDatabaseField(): string
    {
        return match ($this) {
            self::ADD => 'dateAdd',
            self::UPDATE => 'dateUpdate',
        };
    }
}
