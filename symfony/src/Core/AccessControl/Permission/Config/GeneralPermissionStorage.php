<?php

declare(strict_types=1);

namespace App\Core\AccessControl\Permission\Config;

/**
 * Permission constants for system-wide / cross-domain permissions.
 *
 * Domain-specific permissions (user, settings, profile) live in their own
 * storage classes and are auto-discovered by RolePermissionMapper via
 * reflection on PermissionStorageInterface implementations.
 *
 * @see RolePermissionMapper::getDefinedPermissions()
 */
class GeneralPermissionStorage implements PermissionStorageInterface
{
    /** Super-admin: grants access to everything unconditionally. */
    public const ADMIN = 'admin';

    /** Access the EasyAdmin dashboard panel. */
    public const ADMIN_PANEL_ACCESS = 'admin-panel-access';
}
