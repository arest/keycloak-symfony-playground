<?php

declare(strict_types=1);

namespace App\Core\Security\Service;

use App\Core\AccessControl\Permission\Service\PermissionProvider;
use App\Core\Security\Service\UserProvider;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Token\AccessToken;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

class KeycloakAuthenticator extends OAuth2Authenticator implements AuthenticationEntryPointInterface
{
    private const CLIENT_KEY = 'keycloak_pkce';
    private const ATTR_ACCESS_TOKEN = '_keycloak_access_token';
    private const ATTR_USER_DATA = '_keycloak_user_data';

    public function __construct(
        private readonly ClientRegistry $clientRegistry,
        private readonly RouterInterface $router,
        private readonly UserProvider $userProvider,
        private readonly TokenStorage $tokenStorage,
        private readonly PermissionProvider $permissionProvider,
    ) {
    }

    public function supports(Request $request): ?bool
    {
        return $request->attributes->get('_route') === 'login_check';
    }

    public function authenticate(Request $request): Passport
    {
        $client = $this->clientRegistry->getClient(self::CLIENT_KEY);
        /** @var AccessToken $accessToken */
        $accessToken = $this->fetchAccessToken($client);

        /** @var ResourceOwnerInterface $resourceOwner */
        $resourceOwner = $client->fetchUserFromToken($accessToken);
        $userData = $resourceOwner->toArray();

        // Store token and user data on the request so onAuthenticationSuccess
        // can access them without re-fetching (PKCE verifier is consumed above)
        $request->attributes->set(self::ATTR_ACCESS_TOKEN, $accessToken);
        $request->attributes->set(self::ATTR_USER_DATA, $userData);

        // Create or update the local User entity from Keycloak token data.
        // This happens here so the passport carries the Doctrine User object,
        // and the user is persisted even before onAuthenticationSuccess.
        $user = $this->userProvider->createOrUpdateFromKeycloak($userData);

        return new SelfValidatingPassport(
            new UserBadge($user->getUserIdentifier(), fn () => $user),
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        /** @var AccessToken|null $accessToken */
        $accessToken = $request->attributes->get(self::ATTR_ACCESS_TOKEN);
        /** @var array|null $userData */
        $userData = $request->attributes->get(self::ATTR_USER_DATA);

        if ($accessToken && $userData) {
            $this->tokenStorage->saveTokens($accessToken, $userData);

            // Resolve and cache granular permissions from Keycloak realm roles
            $user = $token->getUser();
            if ($user instanceof \App\Entity\User) {
                $this->permissionProvider->setUserPermissions($user);
            }

            $session = $request->getSession();
            if ($session instanceof Session) {
                $session->getFlashBag()->add(
                    'success',
                    'Logged in as ' . ($userData['preferred_username'] ?? 'unknown'),
                );
            }
        }

        return new RedirectResponse($this->router->generate('profile'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $message = strtr($exception->getMessageKey(), $exception->getMessageData());
        $session = $request->getSession();

        if ($session instanceof Session) {
            $session->getFlashBag()->add('error', $message);
        }

        return new RedirectResponse($this->router->generate('login'));
    }

    /**
     * Called when authentication is needed but no credentials are sent.
     * Redirects the user to the Keycloak login page.
     */
    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        return new RedirectResponse(
            $this->router->generate('login'),
            Response::HTTP_TEMPORARY_REDIRECT,
        );
    }
}
