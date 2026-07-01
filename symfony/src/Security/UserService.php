<?php

namespace App\Security;

use App\Entity\User;

/**
 * Facade for user creation and update operations.
 *
 * Validates the input model and delegates persistence to UserManager.
 */
class UserService
{
    use SupportValidation;

    public function __construct(private UserManager $userManager) 
    {
    }

    /**
     * Validate a UserCreateModel and create or update the User entity.
     *
     * @throws \RuntimeException When validation fails
     */
    public function createOrUpdate(UserCreateModel $data): User
    {
        $this->validate($data);

        return $this->userManager->createOrUpdate($data);
    }
}
