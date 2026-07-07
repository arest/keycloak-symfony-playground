<?php

declare(strict_types=1);

namespace App\Core\AccessControl\Permission\Config;

/**
 * Central registry for application permission identifiers.
 *
 * Every permission string used in @IsGranted(), denyAccessUnlessGranted(),
 * or the YAML mapping config should be defined as a constant here so it
 * can be referenced in code and access_control rules.
 */
class GeneralPermissionStorage implements PermissionStorageInterface
{
    /** Super-admin: grants access to everything unconditionally. */
    public const ADMIN = 'admin';

    /** Access the EasyAdmin dashboard panel. */
    public const ADMIN_PANEL_ACCESS = 'adminPanel.access';

    // ── User management ──────────────────────────────────────────

    /** View user list / user details. */
    public const USER_VIEW = 'user.view';

    /** Create, update users. */
    public const USER_MANAGE = 'user.manage';

    /** Delete users. */
    public const USER_DELETE = 'user.delete';

    // ── Settings ─────────────────────────────────────────────────

    /** View application settings. */
    public const SETTINGS_VIEW = 'settings.view';

    /** Edit application settings. */
    public const SETTINGS_EDIT = 'settings.edit';

    // ── Profile (self) ───────────────────────────────────────────

    /** Access own profile. */
    public const PROFILE_VIEW = 'profile.view';

    /** Edit own profile. */
    public const PROFILE_EDIT = 'profile.edit';

    // ── User-level access ────────────────────────────────────────

    /** General user-level access. */
    public const USER_ACCESS = 'user.access';

    /**
     * @return list<string> All defined permission identifiers.
     */
    public static function all(): array
    {
        return [
            self::ADMIN,
            self::ADMIN_PANEL_ACCESS,
            self::USER_VIEW,
            self::USER_MANAGE,
            self::USER_DELETE,
            self::SETTINGS_VIEW,
            self::SETTINGS_EDIT,
            self::PROFILE_VIEW,
            self::PROFILE_EDIT,
            self::USER_ACCESS,
        ];
    }
}
