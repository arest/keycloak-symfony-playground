<?php

namespace App\Controller;

use App\Core\Security\Service\TokenStorage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractController
{
    public function __construct(
        private readonly TokenStorage $tokenStorage,
    ) {
    }

    #[Route('/', name: 'home')]
    public function home(): Response
    {
        return $this->render('dashboard/home.html.twig');
    }

    #[Route('/dashboard', name: 'dashboard')]
    public function dashboard(Request $request): Response
    {
        return $this->render('dashboard/dashboard.html.twig', [
            'user' => $this->tokenStorage->getUserData(),
            'token' => $this->tokenStorage->getTokenData(),
        ]);
    }
}
