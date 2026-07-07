<?php

declare(strict_types=1);

namespace App\Core\AccessControl\Permission\Service;

use App\Entity\User;

class PermissionService
{
    public function __construct(
        private readonly PermissionProvider $permissionProvider,
    ) {
    }

    public function getDefinedPermissions(): array
    {
        return $this->permissionProvider->getDefinedPermissions();
    }

    public function getUserPermissions(User $user): array
    {
        return $this->permissionProvider->getUserPermissions($user);
    }

    public function setUserPermissions(User $user): void
    {
        $this->permissionProvider->setUserPermissions($user);
    }
}
