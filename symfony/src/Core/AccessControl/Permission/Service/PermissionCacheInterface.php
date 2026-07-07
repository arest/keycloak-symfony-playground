<?php

declare(strict_types=1);

namespace App\Core\AccessControl\Permission\Service;

/**
 * Abstraction for storing and retrieving the current user's resolved
 * permission set.
 *
 * Default implementation stores permissions in the Symfony session.
 * Swap for a Redis-backed implementation when session sharing or
 * cross-process caching is needed.
 */
interface PermissionCacheInterface
{
    /**
     * Persist the resolved permission list for the current user.
     *
     * @param list<string> $permissions
     */
    public function setPermissions(array $permissions): void;

    /**
     * Retrieve the resolved permission list for the current user.
     *
     * @return list<string>
     */
    public function getPermissions(): array;

    /**
     * Remove stored permissions (used on logout).
     */
    public function clear(): void;
}
