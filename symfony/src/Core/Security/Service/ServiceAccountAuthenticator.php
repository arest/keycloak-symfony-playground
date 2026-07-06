<?php

declare(strict_types=1);

namespace App\Core\Security\Service;

use League\OAuth2\Client\Provider\GenericProvider;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Exception\InvalidArgumentException;

/**
 * Obtains and caches access tokens using the OAuth2 Client Credentials grant.
 *
 * Used for machine-to-machine (service account) authentication with Keycloak.
 * Tokens are cached in the Symfony cache pool with a TTL slightly under the
 * token's expires_in value to avoid serving stale tokens.
 *
 * The GenericProvider is injected via DI (defined in services.yaml) rather than
 * constructed here, respecting the Dependency Inversion Principle.
 */
final class ServiceAccountAuthenticator
{
    private const CACHE_KEY = 'service_app_access_token';
    private const CACHE_TTL_BUFFER = 30; // seconds to subtract from expires_in for safe early refresh

    public function __construct(
        private readonly GenericProvider $provider,
        private readonly CacheItemPoolInterface $cache,
    ) {
    }

    /**
     * Returns a valid access token, fetching or refreshing as needed.
     *
     * @return string The Bearer access token
     *
     * @throws \RuntimeException If token acquisition fails
     */
    public function getAccessToken(): string
    {
        // Check cache first
        $cached = $this->getCachedToken();
        if ($cached !== null) {
            return $cached;
        }

        // Fetch a new token via Client Credentials grant
        try {
            /** @var \League\OAuth2\Client\Token\AccessToken $accessToken */
            $accessToken = $this->provider->getAccessToken('client_credentials');
            $tokenString = $accessToken->getToken();
            $expiresIn = $accessToken->getExpires() - time();

            $this->cacheToken($tokenString, $expiresIn);

            return $tokenString;
        } catch (\Throwable $e) {
            throw new \RuntimeException(
                \sprintf('Failed to obtain service account token: %s', $e->getMessage()),
                0,
                $e,
            );
        }
    }

    /**
     * Forcefully invalidates the cached token, ensuring the next call fetches a fresh one.
     */
    public function invalidateCache(): void
    {
        try {
            $this->cache->deleteItem(self::CACHE_KEY);
        } catch (InvalidArgumentException) {
            // Ignore invalid cache key errors
        }
    }

    private function getCachedToken(): ?string
    {
        try {
            $item = $this->cache->getItem(self::CACHE_KEY);
        } catch (InvalidArgumentException) {
            return null;
        }

        if (!$item->isHit()) {
            return null;
        }

        $value = $item->get();

        return \is_string($value) && $value !== '' ? $value : null;
    }

    private function cacheToken(string $token, int $expiresIn): void
    {
        try {
            $item = $this->cache->getItem(self::CACHE_KEY);
            $item->set($token);

            // Cache for slightly less than the token's lifetime to avoid serving
            // a token that expires mid-request
            $ttl = max(1, $expiresIn - self::CACHE_TTL_BUFFER);
            $item->expiresAfter($ttl);

            $this->cache->save($item);
        } catch (InvalidArgumentException) {
            // Silently fail — next request will fetch a new token
        }
    }
}
