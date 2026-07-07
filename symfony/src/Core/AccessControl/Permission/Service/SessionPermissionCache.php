<?php

declare(strict_types=1);

namespace App\Core\AccessControl\Permission\Service;

use Symfony\Component\HttpFoundation\Exception\SessionNotFoundException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Stores resolved permissions in the Symfony session alongside OIDC token data.
 *
 * Permissions are stored under a dedicated key so they survive across requests
 * within the same session without hitting the database or Keycloak again.
 */
final class SessionPermissionCache implements PermissionCacheInterface
{
    private const SESSION_KEY = '_permissions';

    public function __construct(
        private readonly RequestStack $requestStack,
    ) {
    }

    /**
     * @param list<string> $permissions
     */
    public function setPermissions(array $permissions): void
    {
        $session = $this->getSession();
        if ($session === null) {
            return;
        }

        $session->set(self::SESSION_KEY, $permissions);
    }

    /**
     * @return list<string>
     */
    public function getPermissions(): array
    {
        $session = $this->getSession();
        if ($session === null) {
            return [];
        }

        /** @var list<string> $permissions */
        $permissions = $session->get(self::SESSION_KEY, []);

        return $permissions;
    }

    public function clear(): void
    {
        $session = $this->getSession();
        if ($session === null) {
            return;
        }

        $session->remove(self::SESSION_KEY);
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
