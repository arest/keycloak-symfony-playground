<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class LogoutController extends AbstractController
{
    #[Route('/logout', name: 'logout')]
    public function logout(Request $request): Response
    {
        // Get the ID token from session for Keycloak back-channel logout
        $session = $request->getSession();
        $tokenData = $session->get('oidc_token', []);
        $idTokenHint = $tokenData['id_token'] ?? null;

        // Clear the Symfony session
        $session->invalidate();

        // Build Keycloak logout URL
        $keycloakLogoutUrl = sprintf(
            'http://localhost:8081/realms/%s/protocol/openid-connect/logout',
            'playground'
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
