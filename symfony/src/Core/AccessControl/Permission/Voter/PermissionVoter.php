<?php

declare(strict_types=1);

namespace App\Core\AccessControl\Permission\Voter;

use App\Core\AccessControl\Permission\Config\GeneralPermissionStorage;
use App\Core\AccessControl\Permission\Service\PermissionService;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class PermissionVoter extends Voter
{
    public function __construct(
        private readonly PermissionService $permissionService,
        private readonly Security $security,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, $this->permissionService->getDefinedPermissions(), true);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            return false;
        }

        $permissions = $this->permissionService->getUserPermissions($user);

        if ($this->isAdmin($permissions)) {
            return true;
        }

        return in_array($attribute, $permissions, true);
    }

    private function isAdmin(array $userPermissions): bool
    {
        return in_array(GeneralPermissionStorage::ADMIN, $userPermissions, true);
    }
}
