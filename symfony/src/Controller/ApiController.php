<?php

namespace App\Controller;

use App\Entity\User;
use App\Core\Security\Voter\ApiAccessVoter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

#[Route('/api', name: 'api_')]
class ApiController extends AbstractController
{
    /**
     * Returns the authenticated user's profile.
     *
     * @return JsonResponse User profile or 401 if unauthenticated
     */
    #[Route('/me', name: 'me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user instanceof User) {
            return new JsonResponse(
                ['error' => 'unauthorized', 'message' => 'Authentication is required.'],
                Response::HTTP_UNAUTHORIZED,
            );
        }

        return new JsonResponse([
            'email' => $user->getEmail(),
            'username' => $user->getUsername(),
            'roles' => $user->getRoles(),
            'lastLogin' => $user->getLastLogin()?->format(\DateTimeInterface::ATOM),
        ]);
    }

    /**
     * Protected resource accessible only to ADMIN users.
     *
     * Uses the ApiAccessVoter to enforce role-based access.
     *
     * @return JsonResponse Success message or 403 for non-admin users
     */
    #[Route('/protected', name: 'protected', methods: ['GET'])]
    public function protected(): JsonResponse
    {
        try {
            $this->denyAccessUnlessGranted(ApiAccessVoter::ATTR_ROLE, 'protected');
        } catch (AccessDeniedException) {
            return new JsonResponse(
                ['error' => 'forbidden', 'message' => 'Access denied. ADMIN role is required.'],
                Response::HTTP_FORBIDDEN,
            );
        }

        /** @var User $user */
        $user = $this->getUser();

        return new JsonResponse([
            'message' => 'Welcome, admin! You have access to the protected resource.',
            'username' => $user->getUsername(),
        ]);
    }
}
