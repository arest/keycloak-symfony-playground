<?php

declare(strict_types=1);

namespace App\Core\Keycloak\Service;

use App\Core\Security\Service\ServiceAccountAuthenticator;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Wraps Keycloak Admin REST API operations using a service account token.
 *
 * All requests are authenticated via the ServiceAccountAuthenticator which
 * obtains and caches tokens using the Client Credentials grant.
 */
final class KeycloakAdminApiClient
{
    private const API_PREFIX = '/admin/realms/%s';

    public function __construct(
        private readonly ServiceAccountAuthenticator $serviceAccountAuthenticator,
        private readonly HttpClientInterface $httpClient,
        private readonly string $keycloakServerUrl,
        private readonly string $keycloakRealm,
    ) {
    }

    /**
     * List users from the Keycloak realm.
     *
     * @param array<string, mixed> $query Optional query parameters (search, max, first, etc.)
     *
     * @return array<int, array<string, mixed>>
     *
     * @throws \RuntimeException On HTTP or transport errors
     */
    public function listUsers(array $query = []): array
    {
        $url = $this->buildUrl('/users', $query);

        return $this->request('GET', $url);
    }

    /**
     * Create a new user in the Keycloak realm.
     *
     * @param array<string, mixed> $userData User data (username, email, enabled, firstName, lastName, etc.)
     *
     * @return string|null The user ID (location header) or null if not returned
     *
     * @throws \RuntimeException On HTTP or transport errors
     */
    public function createUser(array $userData): ?string
    {
        $url = $this->buildUrl('/users');

        $response = $this->requestRaw('POST', $url, [
            'json' => $userData,
        ]);

        // The create response returns 201 with a Location header pointing to the new user
        $location = $response->getHeaders(false)['location'][0] ?? null;

        if ($location !== null) {
            // Extract the user ID from the location header: /admin/realms/{realm}/users/{id}
            $parts = explode('/', rtrim($location, '/'));

            return end($parts) ?: null;
        }

        return null;
    }

    /**
     * Get a specific user by ID.
     *
     * @param string $userId The Keycloak user ID
     *
     * @return array<string, mixed>
     *
     * @throws \RuntimeException
     */
    public function getUser(string $userId): array
    {
        $url = $this->buildUrl(\sprintf('/users/%s', $userId));

        return $this->request('GET', $url);
    }

    /**
     * Performs an authenticated request and decodes JSON response.
     *
     * @return array<int|string, mixed>
     */
    private function request(string $method, string $url): array
    {
        $response = $this->requestRaw($method, $url);

        try {
            $content = $response->getContent();
        } catch (TransportExceptionInterface | \RuntimeException $e) {
            throw new \RuntimeException(\sprintf('Failed to read response from Keycloak Admin API: %s', $e->getMessage()), 0, $e);
        }

        if ($content === '') {
            return [];
        }

        try {
            return json_decode($content, true, 512, JSON_THROW_ON_ERROR) ?? [];
        } catch (\JsonException $e) {
            throw new \RuntimeException(\sprintf('Failed to decode Keycloak Admin API response: %s', $e->getMessage()), 0, $e);
        }
    }

    /**
     * Performs an authenticated request without decoding.
     *
     * @return \Symfony\Contracts\HttpClient\ResponseInterface
     */
    private function requestRaw(string $method, string $url, array $options = [])
    {
        $token = $this->serviceAccountAuthenticator->getAccessToken();

        // If token was invalid (401), invalidate cache and retry once
        $maxRetries = 1;
        $attempt = 0;

        do {
            try {
                $response = $this->httpClient->request($method, $url, array_merge([
                    'headers' => [
                        'Authorization' => \sprintf('Bearer %s', $token),
                        'Content-Type' => 'application/json',
                    ],
                ], $options));

                $statusCode = $response->getStatusCode();

                if ($statusCode === 401 && $attempt < $maxRetries) {
                    // Token might have expired — invalidate cache and retry
                    $this->serviceAccountAuthenticator->invalidateCache();
                    $token = $this->serviceAccountAuthenticator->getAccessToken();
                    ++$attempt;

                    continue;
                }

                if ($statusCode >= 400) {
                    $errorBody = '';
                    try {
                        $errorBody = $response->getContent(false);
                    } catch (\Throwable) {
                        // Ignore — use empty error body
                    }

                    throw new \RuntimeException(
                        \sprintf('Keycloak Admin API returned %d: %s', $statusCode, $errorBody),
                    );
                }

                return $response;
            } catch (TransportExceptionInterface $e) {
                throw new \RuntimeException(
                    \sprintf('Keycloak Admin API transport error: %s', $e->getMessage()),
                    0,
                    $e,
                );
            }
        } while ($attempt <= $maxRetries);

        throw new \RuntimeException('Keycloak Admin API request failed after retry');
    }

    /**
     * Build the full Admin API URL.
     */
    private function buildUrl(string $path, array $query = []): string
    {
        $base = sprintf(
            '%s%s%s',
            rtrim($this->keycloakServerUrl, '/'),
            sprintf(self::API_PREFIX, $this->keycloakRealm),
            $path,
        );

        if ($query !== []) {
            $queryString = http_build_query($query, '', '&', PHP_QUERY_RFC3986);
            $base .= '?' . $queryString;
        }

        return $base;
    }
}
