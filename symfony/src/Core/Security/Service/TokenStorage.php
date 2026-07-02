<?php

declare(strict_types=1);

namespace App\Core\Security\Service;

use League\OAuth2\Client\Token\AccessToken;
use Symfony\Component\HttpFoundation\Exception\SessionNotFoundException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Single point of truth for OIDC token storage (access_token, refresh_token, id_token)
 * and user data, persisted in the Symfony session.
 *
 * Every consumer should go through this service instead of touching the session
 * keys directly.
 */
final class TokenStorage
{
    private const KEY_TOKEN = 'oidc_token';
    private const KEY_USER = 'oidc_user';

    public function __construct(
        private readonly RequestStack $requestStack,
    ) {
    }

    // ─── Token lifecycle ────────────────────────────────────────────

    /**
     * Persist the full OIDC token data and user info from a successful
     * authentication or token refresh.
     */
    public function saveTokens(AccessToken $accessToken, array $userData = []): void
    {
        $session = $this->getSession();
        if (!$session) {
            return;
        }

        $tokenValues = $accessToken->getValues();

        $session->set(self::KEY_TOKEN, [
            'access_token' => $accessToken->getToken(),
            'refresh_token' => $accessToken->getRefreshToken(),
            'id_token' => $tokenValues['id_token'] ?? null,
            'expires_in' => $accessToken->getExpires(),
        ]);

        if ($userData !== []) {
            $session->set(self::KEY_USER, $userData);
        }
    }

    /**
     * Persist a token array directly (used internally by token refresh
     * where we only re-hydrate the array rather than a full AccessToken).
     */
    public function saveTokenData(array $tokenData, ?array $userData = null): void
    {
        $session = $this->getSession();
        if (!$session) {
            return;
        }

        $session->set(self::KEY_TOKEN, $tokenData);

        if ($userData !== null) {
            $session->set(self::KEY_USER, $userData);
        }
    }

    /**
     * Remove all OIDC data from the session.
     */
    public function clear(): void
    {
        $session = $this->getSession();
        if (!$session) {
            return;
        }

        $session->remove(self::KEY_TOKEN);
        $session->remove(self::KEY_USER);
    }

    // ─── Token getters ──────────────────────────────────────────────

    public function getAccessToken(): ?string
    {
        return $this->getTokenValue('access_token');
    }

    public function getRefreshToken(): ?string
    {
        return $this->getTokenValue('refresh_token');
    }

    public function getIdToken(): ?string
    {
        return $this->getTokenValue('id_token');
    }

    public function getExpiresAt(): ?int
    {
        $value = $this->getTokenValue('expires_in');

        return $value !== null ? (int) $value : null;
    }

    /**
     * @return array<string, mixed>
     */
    public function getTokenData(): array
    {
        $session = $this->getSession();

        return $session?->get(self::KEY_TOKEN, []) ?? [];
    }

    /**
     * @return array<string, mixed>
     */
    public function getUserData(): array
    {
        $session = $this->getSession();

        return $session?->get(self::KEY_USER, []) ?? [];
    }

    public function hasTokens(): bool
    {
        return $this->getAccessToken() !== null;
    }

    public function isAuthenticated(): bool
    {
        return $this->getUserData() !== [];
    }

    // ─── Helpers ────────────────────────────────────────────────────

    private function getTokenValue(string $key): ?string
    {
        $data = $this->getTokenData();

        return isset($data[$key]) ? (string) $data[$key] : null;
    }

    private function getSession(): ?SessionInterface
    {
        try {
            return $this->requestStack->getSession();
        } catch (SessionNotFoundException) {
            return null;
        }
    }
}
