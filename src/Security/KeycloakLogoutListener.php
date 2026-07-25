<?php

declare(strict_types=1);

namespace App\Security;

use Mainick\KeycloakClientBundle\Interface\IamClientInterface;
use Mainick\KeycloakClientBundle\Token\KeycloakResourceOwner;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Http\Event\LogoutEvent;

final readonly class KeycloakLogoutListener
{
    public function __construct(
        private string $hubUrl,
        private LoggerInterface $keycloakClientLogger,
        private IamClientInterface $iamClient,
    ) {
    }
   
    /**
     * Event to fix the URL computed in LogoutAuthListener.php from mainick/keycloak-bundle.
     */
    public function __invoke(LogoutEvent $event): void
    {
        if (null === $event->getToken() || null === $event->getToken()->getUser()) {
            return;
        }

        $user = $event->getToken()->getUser();
        if (!$user instanceof KeycloakResourceOwner) {
            return;
        }

        $accessToken = $user->getAccessToken();
        $logoutUrl = $this->iamClient->logoutUrl([
            'access_token' => $accessToken,
            'state' => $accessToken->getValues()['session_state'],
            'id_token_hint' => $accessToken->getValues()['id_token'],
            'post_logout_redirect_uri' => $this->hubUrl,
        ]);
        $this->keycloakClientLogger->info('KeycloakLogoutListener::__invoke', [
            'logoutUrl' => $logoutUrl,
        ]);

        $event->setResponse(new RedirectResponse($logoutUrl));
    }
}
