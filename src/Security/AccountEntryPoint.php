<?php

declare(strict_types=1);

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

class AccountEntryPoint implements AuthenticationEntryPointInterface
{
    public function __construct(private string $hubLoginRoute)
    {
    }

    public function start(Request $request, ?AuthenticationException $authException = null): RedirectResponse
    {
        if (str_starts_with($this->hubLoginRoute, '/')) {
            $serverBaseUrl = $request->getSchemeAndHttpHost();
            $accountUrl = $serverBaseUrl . $this->hubLoginRoute;
        } else {
            $accountUrl = $this->hubLoginRoute;
        }

        $request->getSession()->set('_login_target_path', $request->getUri());

        return new RedirectResponse($accountUrl);
    }
}
