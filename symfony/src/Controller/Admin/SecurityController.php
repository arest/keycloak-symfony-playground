<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Admin\Exception\AdminPanelException;
use KnpU\OAuth2ClientBundle\Client\Provider\KeycloakClient;
use Stevenmaguire\OAuth2\Client\Provider\Keycloak;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

#[Route('/admin/security', name: 'admin_security_')]
class SecurityController extends AbstractController
{
    public function __construct(
        private readonly KeycloakClient $keycloakClient,
        private readonly RouterInterface $router,
    ) {
    }

    #[Route('/login', name: 'login', methods: 'GET')]
    public function login(): RedirectResponse
    {
        return $this->keycloakClient->redirect();
    }

    #[Route('/check', name: 'check', methods: 'GET')]
    public function check(): RedirectResponse
    {
        return $this->redirectToRoute('admin_dashboard');
    }

    #[Route('/logout', name: 'logout', methods: 'GET')]
    public function logout(): void
    {
    }

    #[Route('/logout/redirect', name: 'logout_redirect', methods: 'GET')]
    public function logoutRedirect(): RedirectResponse
    {
        $provider = $this->keycloakClient->getOAuth2Provider();

        if (!$provider instanceof Keycloak) {
            throw new AdminPanelException(
                sprintf('OAuth2 Provider must be an instance of %s.', Keycloak::class)
            );
        }

        $logoutUrl = $provider->getLogoutUrl();
        $queryString = (string)parse_url($logoutUrl, PHP_URL_QUERY);

        parse_str($queryString, $queryParams);


        $redirectUri = isset($queryParams['redirect_uri']) && is_string($queryParams['redirect_uri']) ? $queryParams['redirect_uri'] : '';
        $correctRedirectUri = $this->router->generate(
            'admin_dashboard',
            referenceType: UrlGeneratorInterface::ABSOLUTE_URL
        );

        $finalLogoutUrl = str_replace(urlencode($redirectUri), urlencode($correctRedirectUri), $logoutUrl);

        return $this->redirect($finalLogoutUrl);
    }
}
