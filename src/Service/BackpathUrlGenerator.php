<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;
use Symfony\Component\Routing\RequestContext;

class BackpathUrlGenerator
{
    public function __construct(private RequestStack $requestStack, private UrlGeneratorInterface $urlGenerator, private UrlMatcherInterface $urlMatcher) {}

    /**
     * Generate the backpath if exists and valid and authorized, or the given route otherwise.
     */
    public function generate(string $defaultRoute, array $forbiddenRoutes = []): string
    {
        $backpath = $this->requestStack->getMainRequest()->query->get('backpath');

        if (empty($backpath) || !preg_match('/^\/.*/', $backpath)) {
            $route = null;
        } else {
            $originalContext = $this->urlMatcher->getContext();
            $this->urlMatcher->setContext((new RequestContext())->setMethod('GET'));

            try {
                $match = $this->urlMatcher->match(parse_url($backpath, PHP_URL_PATH));
                $route = $match['_route'];
            } catch (ResourceNotFoundException|MethodNotAllowedException $e) {
                $route = null;
            } finally {
                $this->urlMatcher->setContext($originalContext);
            }
        }

        if (null === $route || in_array($route, $forbiddenRoutes)) {
            return $this->urlGenerator->generate($defaultRoute);
        }

        return $backpath;
    }
}
