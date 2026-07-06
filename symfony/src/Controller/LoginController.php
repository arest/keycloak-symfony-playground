<?php

namespace App\Controller;

use KnpU\OAuth2ClientBundle\Client\Provider\Pkce\KeycloakPkceClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;

class LoginController extends AbstractController
{
    public function __construct(
        private readonly KeycloakPkceClient $keycloakPkceClient,
    ) {
    }

    #[Route('/login', name: 'login')]
    public function login(): RedirectResponse
    {
        return $this->keycloakPkceClient->redirect([
            'openid',
            'profile',
            'email',
            'roles',
        ]);

    }

    /**
     * This route is handled by App\Security\KeycloakAuthenticator.
     * The method exists only to register the route for the router.
     */
    #[Route('/login/check', name: 'login_check')]
    public function check(): void
    {
        throw new \RuntimeException('This should never be reached: the authenticator intercepts this route.');
    }
}
