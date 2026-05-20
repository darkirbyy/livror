<?php

declare(strict_types=1);

namespace App\Service;

use Mainick\KeycloakClientBundle\Exception\KeycloakAuthenticationException;
use Mainick\KeycloakClientBundle\Interface\AccessTokenInterface;
use Mainick\KeycloakClientBundle\Provider\KeycloakAdminClient;
use Mainick\KeycloakClientBundle\Token\AccessToken;
use Psr\Log\LoggerInterface;

class KeycloakClient extends KeycloakAdminClient
{
    private string $baseUrl;
    private string $adminRealm;
    private string $adminClientId;
    private string $adminClientSecret;

    public function __construct(
        private LoggerInterface $logger,
        bool $verify_ssl,
        string $base_url,
        string $admin_realm,
        string $admin_client_id,
        string $admin_username, // unused, but mandatory for the parent
        string $admin_password, // unused, but mandatory for the parent
        string $version,
        string $admin_client_secret,
    ) {
        parent::__construct($logger, $verify_ssl, $base_url, $admin_realm, $admin_client_id, $admin_username, $admin_password, $version);
        $this->baseUrl = $base_url;
        $this->adminRealm = $admin_realm;
        $this->adminClientId = $admin_client_id;
        $this->adminClientSecret = $admin_client_secret;
    }

    /**
     * Override : get an access token using client_credentials
     * before Service.php tries to do it with password.
     */
    public function getAdminAccessToken(): ?AccessTokenInterface
    {
        $current = parent::getAdminAccessToken();

        // Check if token is still valid
        if (null !== $current && false === $current->hasExpired()) {
            return $current;
        }

        // If not, get a new one
        $this->refreshViaClientCredentials();

        return parent::getAdminAccessToken();
    }

    /**
     * Inspired by Service.php from mainick/keycloak-bundle, lines 134-174.
     */
    private function refreshViaClientCredentials(): void
    {
        try {
            $token = $this->getKeycloakProvider()->getAccessToken('client_credentials', [
                'client_id' => $this->adminClientId,
                'client_secret' => $this->adminClientSecret,
            ]);

            $accessToken = new AccessToken();
            $accessToken->setToken($token->getToken())->setExpires($token->getExpires())->setValues($token->getValues());

            $this->logger->info('KeycloakAdminClient::getAdminAccessToken', [
                'token' => $accessToken->getToken(),
                'expires' => $accessToken->getExpires(),
            ]);

            $this->setAdminAccessToken($accessToken);
        } catch (\Exception $e) {
            $this->logger->error('KeycloakAdminClient::getAdminAccessToken', [
                'error' => 'Authentication failed to Keycloak Admin API - ' . $e->getMessage() . ' - ' . $e->getTraceAsString(),
            ]);

            throw new KeycloakAuthenticationException('Authentication failed to Keycloak Admin API');
        }
    }
}
