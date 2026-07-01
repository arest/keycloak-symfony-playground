<?php

namespace App\Security;

/**
 * DTO carrying the user data extracted from a Keycloak token.
 */
final readonly class KeycloakUserData
{
    /**
     * @param list<string> $realmRoles Raw Keycloak role names (e.g. 'USER', 'ADMIN')
     */
    public function __construct(
        public string $keycloakId,
        public ?string $email,
        public string $username,
        public array $realmRoles,
    ) {
    }

    /**
     * Factory from the decoded Keycloak token array.
     *
     * @param array<string, mixed> $userData
     */
    public static function fromArray(array $userData): self
    {
        $keycloakId = $userData['sub'];

        return new self(
            keycloakId: $keycloakId,
            email: $userData['email'] ?? null,
            username: $userData['preferred_username'] ?? $userData['email'] ?? $keycloakId,
            realmRoles: $userData['realm_roles']
                ?? $userData['realm_access']['roles']
                ?? [],
        );
    }
}
