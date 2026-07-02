<?php

namespace App\Controller;

use App\Core\Security\Service\TokenStorage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class LogoutController extends AbstractController
{
    public function __construct(
        private readonly TokenStorage $tokenStorage,
        private readonly string $keycloakServerUrlExternal,
        private readonly string $keycloakRealm,
    ) {
    }

    #[Route('/logout', name: 'logout')]
    public function logout(Request $request): Response
    {
        // Get the ID token from session for Keycloak back-channel logout
        $idTokenHint = $this->tokenStorage->getIdToken();

        // Clear the Symfony session
        $session = $request->getSession();
        $session->invalidate();

        // Build Keycloak logout URL
        $keycloakLogoutUrl = sprintf(
            '%s/realms/%s/protocol/openid-connect/logout',
            $this->keycloakServerUrlExternal,
            $this->keycloakRealm
        );

        $params = [
            'post_logout_redirect_uri' => 'http://localhost:3000',
        ];

        if ($idTokenHint) {
            $params['id_token_hint'] = $idTokenHint;
        }

        $redirectUrl = $keycloakLogoutUrl . '?' . http_build_query($params);

        return $this->redirect($redirectUrl);
    }
}
