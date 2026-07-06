<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Keycloak\Service\KeycloakAdminApiClient;
use App\Core\Security\Voter\ApiAccessVoter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Admin API endpoints backed by Keycloak Admin REST API.
 *
 * All endpoints require the ADMIN role. They use the service account
 * (Client Credentials) to authenticate with the Keycloak Admin API.
 */
#[Route('/api/admin', name: 'api_admin_')]
class AdminController extends AbstractController
{
    /**
     * List users from Keycloak.
     *
     * Supports optional query parameters:
     *   - search  (string)  Filter by username, email, first/last name
     *   - max     (int)     Maximum results (default: 100)
     *   - first   (int)     Pagination offset (default: 0)
     *
     * @return JsonResponse List of users or error
     */
    #[Route('/users', name: 'users_list', methods: ['GET'])]
    public function listUsers(Request $request, KeycloakAdminApiClient $adminApiClient): JsonResponse
    {
        try {
            $this->denyAccessUnlessGranted(ApiAccessVoter::ATTR_ROLE, 'protected');
        } catch (AccessDeniedException) {
            return new JsonResponse(
                ['error' => 'forbidden', 'message' => 'Access denied. ADMIN role is required.'],
                Response::HTTP_FORBIDDEN,
            );
        }

        try {
            $query = array_filter([
                'search' => $request->query->get('search'),
                'max' => $request->query->getInt('max', 100),
                'first' => $request->query->getInt('first', 0),
            ], fn ($value) => $value !== null && $value !== '');

            $users = $adminApiClient->listUsers($query);

            return new JsonResponse($users);
        } catch (\RuntimeException $e) {
            return new JsonResponse(
                ['error' => 'admin_api_error', 'message' => $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR,
            );
        }
    }

    /**
     * Create a new user in Keycloak.
     *
     * Accepts JSON body:
     *   - username   (string, required)
     *   - email      (string, optional)
     *   - enabled    (bool,   optional, default: true)
     *   - firstName  (string, optional)
     *   - lastName   (string, optional)
     *
     * @return JsonResponse Created user details or error
     */
    #[Route('/users', name: 'users_create', methods: ['POST'])]
    public function createUser(Request $request, KeycloakAdminApiClient $adminApiClient): JsonResponse
    {
        try {
            $this->denyAccessUnlessGranted(ApiAccessVoter::ATTR_ROLE, 'protected');
        } catch (AccessDeniedException) {
            return new JsonResponse(
                ['error' => 'forbidden', 'message' => 'Access denied. ADMIN role is required.'],
                Response::HTTP_FORBIDDEN,
            );
        }

        $data = json_decode($request->getContent(), true);

        if (!\is_array($data) || empty($data['username'])) {
            return new JsonResponse(
                ['error' => 'validation_error', 'message' => 'The "username" field is required.'],
                Response::HTTP_BAD_REQUEST,
            );
        }

        $userData = [
            'username' => $data['username'],
            'email' => $data['email'] ?? null,
            'enabled' => $data['enabled'] ?? true,
            'firstName' => $data['firstName'] ?? null,
            'lastName' => $data['lastName'] ?? null,
        ];

        // Remove null values so Keycloak doesn't set them to "null" string
        $userData = array_filter($userData, fn ($value) => $value !== null);

        try {
            $userId = $adminApiClient->createUser($userData);

            if ($userId === null) {
                return new JsonResponse(
                    ['error' => 'create_failed', 'message' => 'User was created but no ID was returned.'],
                    Response::HTTP_INTERNAL_SERVER_ERROR,
                );
            }

            // Fetch the created user to return full details
            $user = $adminApiClient->getUser($userId);

            return new JsonResponse($user, Response::HTTP_CREATED);
        } catch (\RuntimeException $e) {
            return new JsonResponse(
                ['error' => 'admin_api_error', 'message' => $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR,
            );
        }
    }
}
