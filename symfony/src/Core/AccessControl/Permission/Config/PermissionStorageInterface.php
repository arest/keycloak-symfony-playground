<?php

declare(strict_types=1);

namespace App\Core\AccessControl\Permission\Config;

/**
 * Marker interface for permission storage classes.
 *
 * Every class implementing this interface is auto-discovered by
 * RolePermissionMapper via tagged DI services + reflection.
 *
 * All public string constants defined on the class are treated as
 * valid application permission identifiers and are collected by
 * RolePermissionMapper::getDefinedPermissions().
 *
 * @see RolePermissionMapper
 */
interface PermissionStorageInterface
{
}
