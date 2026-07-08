<?php

declare(strict_types=1);

namespace App\Core\AccessControl\Permission\Config;

/**
 * Permission constants for the User domain.
 *
 * Every permission string used in @IsGranted(), denyAccessUnlessGranted(),
 * or the YAML mapping config should be defined as a constant here so it
 * can be referenced in code and auto-discovered by RolePermissionMapper.
 *
 * @see RolePermissionMapper::getDefinedPermissions() — uses reflection
 *      to collect all public string constants from every storage class.
 */
class UserPermissionStorage implements PermissionStorageInterface
{
    /** View user list / user details. */
    public const USER_VIEW = 'user-view';

    /** Create, update users. */
    public const USER_MANAGE = 'user-manage';

    /** Delete users. */
    public const USER_DELETE = 'user-delete';
}
