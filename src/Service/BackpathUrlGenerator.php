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
    public function __construct(
        private string $defaultUri,
        private RequestStack $requestStack,
        private UrlGeneratorInterface $urlGenerator,
        private UrlMatcherInterface $urlMatcher,
    ) {}

    /**
     * Generate the backpath if exists and valid and authorized, or the given route otherwise.
     */
    public function generate(string $defaultRoute, array $forbiddenRoutes = []): string
    {
        $backpath = $this->requestStack->getMainRequest()->query->get('backpath');
        $baseUriTrimmed = rtrim(parse_url($this->defaultUri, PHP_URL_PATH), '/');

        // pass if backpath is empty or not valid (should start with '/' or '/<sub-folder>/' )
        if (empty($backpath) || !str_starts_with($backpath, $baseUriTrimmed . '/')) {
            $route = null;
        } else {
            // save the original context then set a new one with only GET method
            $originalContext = $this->urlMatcher->getContext();
            $this->urlMatcher->setContext((new RequestContext())->setMethod('GET'));

            try {
                // if the app is served on a sub-folder, remove it from the backpath before matching
                $backpathTrimmed = preg_replace('/^' . preg_quote($baseUriTrimmed, '/') . '/', '', $backpath);

                // try to match a route and extract it if success
                $match = $this->urlMatcher->match(parse_url($backpathTrimmed, PHP_URL_PATH));
                $route = $match['_route'];
            } catch (ResourceNotFoundException|MethodNotAllowedException $e) {
                // pass if no match or not valid method
                $route = null;
            } finally {
                // in any case restore the original context
                $this->urlMatcher->setContext($originalContext);
            }
        }

        // if no route found or route is forbidden, generate a default path
        if (null === $route || in_array($route, $forbiddenRoutes)) {
            return $this->urlGenerator->generate($defaultRoute);
        }

        return $backpath;
    }
}
