<?php

declare(strict_types=1);

namespace App\Security;

use App\Service\AccountUrlGenerator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

class AccountEntryPoint implements AuthenticationEntryPointInterface
{
    public function __construct(private AccountUrlGenerator $accountUrlGenerator)
    {
    }

    public function start(Request $request, ?AuthenticationException $authException = null): RedirectResponse
    {
        $request->getSession()->set('_login_target_path', $request->getUri());

        return new RedirectResponse($this->accountUrlGenerator->getHubLoginUrl());
    }
}
