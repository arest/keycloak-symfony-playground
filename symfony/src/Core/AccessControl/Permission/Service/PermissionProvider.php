<?php

declare(strict_types=1);

namespace App\Core\AccessControl\Permission\Service;

use App\Core\AccessControl\Permission\Config\RolePermissionMapper;
use App\Entity\User;

/**
 * Resolves, caches, and retrieves the permission set for the current user.
 *
 * Flow:
 *   authenticate() → extract realm roles from ID token
 *                 → map roles → permissions via RolePermissionMapper
 *                 → persist in session via PermissionCacheInterface
 *
 *   voteOnAttribute() → PermissionVoter → PermissionService → PermissionProvider
 *                     → PermissionCacheInterface::getPermissions()
 */
class PermissionProvider
{
    public function __construct(
        private readonly RolePermissionMapper $rolePermissionMapper,
        private readonly PermissionCacheInterface $permissionCache,
    ) {
    }

    /**
     * @return list<string>
     */
    public function getDefinedPermissions(): array
    {
        return $this->rolePermissionMapper->getDefinedPermissions();
    }

    /**
     * @return list<string>
     */
    public function getUserPermissions(User $user): array
    {
        // Try cache first (session or Redis)
        $cached = $this->permissionCache->getPermissions();

        if ($cached !== []) {
            return $cached;
        }

        // Cold cache — compute from user roles and populate
        $permissions = $this->rolePermissionMapper->resolve($user->getRoles());
        $this->permissionCache->setPermissions($permissions);

        return $permissions;
    }

    /**
     * Compute and persist permissions for the given user.
     *
     * Called during authentication to warm the cache so the first
     * request after login does not hit a cold cache.
     */
    public function setUserPermissions(User $user): void
    {
        $permissions = $this->rolePermissionMapper->resolve($user->getRoles());
        $this->permissionCache->setPermissions($permissions);
    }

    /**
     * Compute permissions from raw Symfony roles (no User entity needed).
     *
     * Used during authentication when the User entity is being created.
     *
     * @param list<string> $symfonyRoles
     *
     * @return list<string>
     */
    public function computeFromRoles(array $symfonyRoles): array
    {
        return $this->rolePermissionMapper->resolve($symfonyRoles);
    }
}
