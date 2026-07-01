<?php

namespace App\Security;

use App\Entity\User;
use App\Repository\UserRepository;

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

        if ($data->realmRoles !== null) {
            $user->setRoles($this->mapRoles($data->realmRoles));
        }

        // Update last login timestamp
        $user->setLastLogin(new \DateTimeImmutable());

        $this->userRepository->save($user);

        return $user;
    }

    /**
     * Map Keycloak role names to Symfony roles.
     *
     * @param list<string> $realmRoles
     * @return list<string>
     */
    private function mapRoles(array $realmRoles): array
    {
        $roles = ['ROLE_USER'];

        foreach ($realmRoles as $role) {
            $mapped = match (strtoupper($role)) {
                'USER' => 'ROLE_USER',
                'ADMIN' => 'ROLE_ADMIN',
                default => null,
            };

            if ($mapped !== null) {
                $roles[] = $mapped;
            }
        }

        return array_values(array_unique($roles));
    }
}
