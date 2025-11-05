<?php

declare(strict_types=1);

namespace App\Dto;

class QueryParam
{
    public function __construct(public ?int $offset, public ?int $limit, public ?array $sorts, public ?array $filters)
    {
    }
}
