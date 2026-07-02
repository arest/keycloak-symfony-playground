<?php

namespace App\Core\Security\EventSubscriber;

use App\Core\Security\Service\TokenRefreshService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Hooks TokenRefreshService into the request lifecycle.
 *
 * Before every API request, checks whether the stored OIDC access token is
 * still valid and refreshes it transparently if needed.
 */
final class TokenRefreshSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly TokenRefreshService $tokenRefreshService,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 0],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        // Only trigger for API routes
        if (!str_starts_with($request->getPathInfo(), '/api')) {
            return;
        }

        $this->tokenRefreshService->refreshIfNeeded();
    }
}
