<?php

namespace App\Core\Security\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\User\Service\UserService;
use App\Service\User\Model\UserCreateModel;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class UserProvider implements UserProviderInterface
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserService $userService,
    ) {
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $user = $this->userRepository->findOneBy(['keycloakId' => $identifier]);

        if (!$user instanceof User) {
            throw new UserNotFoundException(sprintf('User with keycloakId "%s" not found.', $identifier));
        }

        return $user;
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof User) {
            throw new \RuntimeException('Unsupported user class.');
        }

        $fresh = $this->userRepository->findOneByKeycloakId($user->getKeycloakId());

        if (!$fresh instanceof User) {
            throw new UserNotFoundException(sprintf('User with keycloakId "%s" not found.', $user->getKeycloakId()));
        }

        return $fresh;
    }

    public function supportsClass(string $class): bool
    {
        return User::class === $class || is_subclass_of($class, User::class);
    }

    /**
     * Create or update a User entity from Keycloak token user data.
     *
     * Extracts both realm roles and client roles (from resource_access)
     * so the UserManager can map the granular client roles to Symfony roles.
     *
     * @param array<string, mixed> $userData The decoded user info from the Keycloak token
     */
    public function createOrUpdateFromKeycloak(array $userData): User
    {
        $keycloakId = $userData['sub'];

        return $this->userService->createOrUpdate(
            new UserCreateModel(
                keycloakId: $keycloakId,
                email: $userData['email'] ?? null,
                username: $userData['preferred_username'] ?? $userData['email'] ?? $keycloakId,
                realmRoles: $userData['realm_roles']
                    ?? $userData['realm_access']['roles']
                    ?? null,
                clientRoles: $userData['resource_access']['symfony-bff']['roles']
                    ?? null,
            )
        );
    }
}
