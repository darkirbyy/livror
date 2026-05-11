<?php

declare(strict_types=1);

namespace App\Tests\Mock;

use App\Service\KeycloakManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

class KeycloakMockEntryPoint implements AuthenticationEntryPointInterface
{
    public function __construct(
        private AuthenticationEntryPointInterface $inner,
        private ParameterBagInterface $parameterBag,
        private TokenStorageInterface $tokenStorage,
        private KeycloakManagerInterface $keycloakManager
    ) {}

    public function start(Request $request, ?AuthenticationException $authException = null): RedirectResponse
    {
        if ($this->parameterBag->get('app.mock_keycloak')) {
            $user = new KeycloakMockUser($this->keycloakManager->createUser(1));
            $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
            $this->tokenStorage->setToken($token);
            $request->getSession()->set('_security_main', serialize($token));
            $request->getSession()->save();

            return new RedirectResponse('/');
        } else {
            return $this->inner->start($request, $authException);
        }
    }
}
