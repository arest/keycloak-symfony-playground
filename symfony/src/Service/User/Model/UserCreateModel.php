<?php

namespace App\Service\User\Model;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Input model for creating or updating a User entity.
 *
 * All properties are optional (nullable). When a property is null, the
 * corresponding value on the existing User entity is left unchanged.
 */
final readonly class UserCreateModel
{
    /**
     * @param list<string>|null $realmRoles Raw Keycloak role names (e.g. 'USER', 'ADMIN')
     */
    public function __construct(
        #[Assert\Length(max: 255)]
        public ?string $keycloakId = null,

        #[Assert\Email]
        #[Assert\Length(max: 255)]
        public ?string $email = null,

        #[Assert\Length(max: 255)]
        public ?string $username = null,

        /**
         * @var list<string>|null
         */
        #[Assert\All([new Assert\Type('string')])]
        public ?array $realmRoles = null,
    ) {
    }
}
