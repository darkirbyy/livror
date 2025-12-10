<?php

declare(strict_types=1);

namespace App\Service;

class HubUrlGenerator
{
    public function __construct(private string $hubBaseUrl, private string $hubAccountRoute)
    {
    }

    /**
     * Generate a route prefixed by the hub root url.
     */
    public function generateRoot(string $route)
    {
        return $this->hubBaseUrl . $route;
    }

    /**
     * Generate a route prefixed by the hub account url, accepting parameters.
     */
    public function generateAccount(string $route, array $parameters = [])
    {
        $url = $this->hubBaseUrl . $this->hubAccountRoute . $route;
        $url .= !empty($parameters) ? '?' . http_build_query($parameters) : '';

        return $url;
    }
}
