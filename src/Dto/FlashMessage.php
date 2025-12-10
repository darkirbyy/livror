<?php

declare(strict_types=1);

namespace App\Dto;

class FlashMessage
{
    public function __construct(public string $message, public array $params = [])
    {
    }
}
