<?php

declare(strict_types=1);

namespace App\Core\AccessControl\Permission\Config;

/**
 * Resolves Symfony roles (e.g. ROLE_ADMIN) to granular application permissions,
 * and provides the authoritative list of all known permissions via reflection
 * on PermissionStorageInterface implementations.
 *
 * Permission storage classes are auto-discovered via tagged DI services
 * and their public string constants are extracted using PHP reflection.
 *
 * The role → permission mapping itself is still driven by the YAML config
 * parameter (%app.permissions.mapping%) until Phase 2 of the Keycloak
 * client-role migration removes the indirection entirely.
 */
final readonly class RolePermissionMapper
{
    /**
     * @param iterable<PermissionStorageInterface> $storages tagged DI collection of storage classes
     */
    public function __construct(
        private iterable $storages = [],
    ) {
    }

    /**
     * Discover all known permission identifiers via reflection on every
     * registered PermissionStorageInterface implementation.
     *
     * Public string constants are collected from each storage class,
     * replacing the previous approach that parsed the YAML mapping.
     *
     * @return list<string>
     */
    public function getDefinedPermissions(): array
    {
        $all = [];

        foreach ($this->storages as $storage) {
            $ref = new \ReflectionClass($storage::class);

            foreach ($ref->getReflectionConstants() as $const) {
                if ($const->isPublic() && \is_string($const->getValue())) {
                    $all[] = $const->getValue();
                }
            }
        }

        return array_values(array_unique($all));
    }
}
