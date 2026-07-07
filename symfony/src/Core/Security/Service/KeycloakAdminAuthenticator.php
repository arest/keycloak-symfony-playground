<?php

declare(strict_types=1);

namespace App\Core\Security\Service;

use App\Core\Security\Service\UserProvider;
use App\Entity\User;
use InvalidArgumentException;
use KnpU\OAuth2ClientBundle\Client\Provider\KeycloakClient;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use RuntimeException;
use Stevenmaguire\OAuth2\Client\Provider\KeycloakResourceOwner;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class KeycloakAdminAuthenticator extends OAuth2Authenticator implements KeycloakAdminAuthenticatorInterface
{
    public function __construct(
        private readonly KeycloakClient $keycloakClient,
        private readonly UserProvider $userProvider,
    ) {
    }

    public function supports(Request $request): ?bool
    {
        return $request->attributes->get('_route') === 'admin_security_check';
    }

    public function authenticate(Request $request): Passport
    {
        $accessToken = $this->fetchAccessToken($this->keycloakClient);

        return new SelfValidatingPassport(
            new UserBadge($accessToken->getToken(), function () use ($accessToken) {
                $keycloakUser = $this->keycloakClient->fetchUserFromToken($accessToken);

                if (!$keycloakUser instanceof KeycloakResourceOwner) {
                    throw new RuntimeException('Keycloak user not found.');
                }

                $userData = $keycloakUser->toArray();

                $email = $keycloakUser->getEmail();

                if (is_null($email)) {
                    throw new InvalidArgumentException('Keycloak user email not found.');
                }

                $user = $this->userProvider->createOrUpdateFromKeycloak($userData);

                if (!$user instanceof User) {
                    throw new RuntimeException('User not found.');
                }

                return $user;
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        $message = strtr($exception->getMessageKey(), $exception->getMessageData());

        return new Response($message, Response::HTTP_FORBIDDEN);
    }
}
