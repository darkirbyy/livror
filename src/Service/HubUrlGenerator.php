<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\HttpFoundation\RequestStack;

class HubUrlGenerator
{
    public function __construct(private RequestStack $requestStack, private string $hubBaseUrl, private string $hubAccountRoute)
    {
    }

    public function generateRoot(string $route)
    {
        return $this->hubBaseUrl . $route;
    }

    public function generateAccount(string $route = '')
    {
        return $this->hubBaseUrl . $this->hubAccountRoute . $route;
    }
}
