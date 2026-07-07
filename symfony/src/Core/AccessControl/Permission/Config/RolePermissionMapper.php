<?php

declare(strict_types=1);

namespace App\Core\AccessControl\Permission\Config;

/**
 * Maps Symfony roles (e.g. ROLE_ADMIN) to granular application permissions
 * based on the `app.permissions.mapping` parameter defined in
 * config/packages/permissions.yaml.
 */
final readonly class RolePermissionMapper
{
    /**
     * @param array<string, list<string>> $mapping role => list of permissions
     */
    public function __construct(
        private array $mapping = [],
    ) {
    }

    /**
     * Resolve all permissions for a given set of Symfony roles.
     *
     * Deduplicates and returns a flat list of permission strings.
     *
     * @param list<string> $symfonyRoles e.g. ['ROLE_USER', 'ROLE_ADMIN']
     *
     * @return list<string>
     */
    public function resolve(array $symfonyRoles): array
    {
        $permissions = [];

        foreach ($symfonyRoles as $role) {
            if (isset($this->mapping[$role])) {
                foreach ($this->mapping[$role] as $permission) {
                    $permissions[] = $permission;
                }
            }
        }

        return array_values(array_unique($permissions));
    }

    /**
     * @return list<string> All known permission identifiers
     */
    public function getDefinedPermissions(): array
    {
        $all = [];

        foreach ($this->mapping as $permissions) {
            foreach ($permissions as $permission) {
                $all[] = $permission;
            }
        }

        return array_values(array_unique($all));
    }
}
