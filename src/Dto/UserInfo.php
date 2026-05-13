<?php

declare(strict_types=1);

namespace App\Dto;

class UserInfo
{
    public function __construct(public User $user, public int $numberReviews) {}
}
