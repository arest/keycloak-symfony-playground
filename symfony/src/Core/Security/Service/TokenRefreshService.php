<?php

namespace App\Core\Security\Service;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use League\OAuth2\Client\Token\AccessToken;
use Psr\Log\LoggerInterface;

/**
 * Refreshes the OIDC access token transparently when it is about to expire.
 *
 * The token data (access_token, refresh_token, expires_in) is stored in the
 * user's session. On each API request this service checks whether the token
 * is still valid and, if not, exchanges the refresh token for a new set of
 * tokens via the Keycloak token endpoint.
 *
 * If refresh fails (e.g. revoked session, network error), the session is
 * invalidated so the user is forced to re-authenticate.
 */
final class TokenRefreshService
{
    private const GRACE_SECONDS = 30;

    public function __construct(
        private readonly ClientRegistry $clientRegistry,
        private readonly TokenStorage $tokenStorage,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Check the current session token and refresh it if needed.
     *
     * @return bool True if the token was refreshed, false otherwise
     */
    public function refreshIfNeeded(): bool
    {
        if (!$this->tokenStorage->hasTokens()) {
            return false;
        }

        $tokenData = $this->tokenStorage->getTokenData();

        if (!isset($tokenData['refresh_token'])) {
            return false;
        }

        if (!$this->isExpired($tokenData['expires_in'] ?? 0)) {
            return false;
        }

        return $this->doRefresh($tokenData);
    }

    /**
     * Perform the actual refresh call to Keycloak.
     */
    private function doRefresh(array $tokenData): bool
    {
        try {
            $client = $this->clientRegistry->getClient('keycloak_pkce');
            $provider = $client->getOAuth2Provider();

            /** @var AccessToken $newToken */
            $newToken = $provider->getAccessToken('refresh_token', [
                'refresh_token' => $tokenData['refresh_token'],
            ]);

            $tokenValues = $newToken->getValues();

            $this->tokenStorage->saveTokenData([
                'access_token' => $newToken->getToken(),
                'refresh_token' => $newToken->getRefreshToken() ?? $tokenData['refresh_token'],
                'id_token' => $tokenValues['id_token'] ?? $tokenData['id_token'] ?? null,
                'expires_in' => $newToken->getExpires(),
            ]);

            $this->logger->info('OIDC access token refreshed successfully.');

            return true;
        } catch (\Throwable $e) {
            $this->logger->warning('OIDC token refresh failed, clearing session.', [
                'error' => $e->getMessage(),
            ]);

            $this->tokenStorage->clear();

            return false;
        }
    }

    /**
     * Check whether the token expires within the grace period.
     *
     * @param int $expiresAt Unix timestamp from the stored token
     */
    private function isExpired(int $expiresAt): bool
    {
        return $expiresAt <= (time() + self::GRACE_SECONDS);
    }
}
