<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Uid\Uuid;

class User
{
    public function __construct(public Uuid $uuid, public string $username, public string $avatarPath) {}
}
