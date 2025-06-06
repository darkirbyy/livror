<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\HttpFoundation\RequestStack;

class AccountUrlGenerator
{
    public function __construct(
        private RequestStack $requestStack,
        private string $hubBaseUrl,
        private string $hubLoginRoute,
        private string $hubLogoutRoute,
        private string $hubAccountRoute,
    ) {
        if (empty($this->hubBaseUrl)) {
            $this->hubBaseUrl = $requestStack->getCurrentRequest->getSchemeAndHttpHost();
        }
    }

    public function getHubLoginUrl(): string
    {
        return $this->hubBaseUrl . $this->hubLoginRoute;
    }

    public function getHubLogoutUrl(): string
    {
        return $this->hubBaseUrl . $this->hubLogoutRoute;
    }

    public function getHubAccountUrl(): string
    {
        return $this->hubBaseUrl . $this->hubAccountRoute;
    }
}
