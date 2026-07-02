<?php

namespace App\Core\Security\Voter;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Voter for API endpoint access control.
 *
 * Supports two subjects:
 * - 'me'        — grants access to any authenticated user (has ROLE_USER)
 * - 'protected' — grants access only to users with ROLE_ADMIN
 */
final class ApiAccessVoter extends Voter
{
    public const ATTR_ROLE = 'API_ACCESS';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return self::ATTR_ROLE === $attribute && \in_array($subject, ['me', 'protected'], true);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        return match ($subject) {
            'me' => true, // Authenticated users all have ROLE_USER
            'protected' => \in_array('ROLE_ADMIN', $user->getRoles(), true),
            default => false,
        };
    }
}
