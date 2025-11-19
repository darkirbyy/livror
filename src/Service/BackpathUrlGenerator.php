<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Service to validate and complete the QueryParam Dto.
 */
final class BackpathUrlGenerator
{
    public function __construct(private RequestStack $requestStack)
    {
    }

    // Generate the backpath if exists and valid, keep the given path otherwise
    public function generate(string $defaultPath): string
    {
        $backpath = $this->requestStack->getMainRequest()->query->get('backpath');
        if (!empty($backpath) && preg_match('/^\/.*/', $backpath)) {
            return $backpath;
        } else {
            return $defaultPath;
        }
    }
}
