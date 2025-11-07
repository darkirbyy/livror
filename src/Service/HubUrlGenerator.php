<?php

declare(strict_types=1);

namespace App\Service;

class HubUrlGenerator
{
    public function __construct(private string $hubBaseUrl, private string $hubAccountRoute)
    {
    }

    public function generateRoot(string $route)
    {
        return $this->hubBaseUrl . $route;
    }

    public function generateAccount(string $route, array $parameters = [])
    {
        $url = $this->hubBaseUrl . $this->hubAccountRoute . $route;
        $url .= !empty($parameters) ? '?' . http_build_query($parameters) : '';

        return $url;
    }
}
