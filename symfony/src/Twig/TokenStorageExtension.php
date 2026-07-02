<?php

declare(strict_types=1);

namespace App\Twig;

use App\Core\Security\Service\TokenStorage;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

/**
 * Makes TokenStorage available as the global `token_storage` variable in all Twig templates.
 *
 * This is required by base.html.twig which uses `token_storage.isAuthenticated`
 * to toggle Login/Logout links in the navigation bar.
 */
final class TokenStorageExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(
        private readonly TokenStorage $tokenStorage,
    ) {
    }

    /**
     * @return array<string, TokenStorage>
     */
    public function getGlobals(): array
    {
        return [
            'token_storage' => $this->tokenStorage,
        ];
    }
}
