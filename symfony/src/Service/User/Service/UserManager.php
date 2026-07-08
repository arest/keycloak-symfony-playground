<?php

namespace App\Service\User\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\User\Model\UserCreateModel;
/**
 * Manages user creation and updates sourced from Keycloak data.
 */
readonly class UserManager
{
    public function __construct(
        private UserRepository $userRepository,
    ) {
    }

    /**
     * Create or update a User entity from a UserCreateModel DTO.
     *
     * The input model is expected to have been validated before being passed
     * here. When a property on the model is null, the corresponding value on
     * the existing User entity is left unchanged.
     *
     * Role resolution priority:
     *   1. Client roles (from resource_access.symfony-bff.roles) — granular
     *   2. Realm roles (backward compatible fallback)
     * Both are merged when present.
     */
    public function createOrUpdate(UserCreateModel $data): User
    {
        $user = $data->keycloakId !== null
            ? $this->userRepository->findOneByKeycloakId($data->keycloakId)
            : null;

        if (!$user instanceof User) {
            $user = new User();

            if ($data->keycloakId !== null) {
                $user->setKeycloakId($data->keycloakId);
            }
        }

        if ($data->email !== null) {
            $user->setEmail($data->email);
        }

        if ($data->username !== null) {
            $user->setUsername($data->username);
        }

        // Collect roles from both client and realm sources
        $roles = ['ROLE_USER'];

        if ($data->clientRoles !== null) {
            $roles = array_merge($roles, $data->clientRoles);
        }

        if ($data->realmRoles !== null) {
            $roles = array_merge($roles, $data->realmRoles);
        }

        $user->setRoles(array_values(array_unique($roles)));

        // Update last login timestamp
        $user->setLastLogin(new \DateTimeImmutable());

        $this->userRepository->save($user);

        return $user;
    }
}
