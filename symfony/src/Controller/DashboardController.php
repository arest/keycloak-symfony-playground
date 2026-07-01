<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function home(): Response
    {
        return $this->render('dashboard/home.html.twig');
    }

    #[Route('/dashboard', name: 'dashboard')]
    public function dashboard(Request $request): Response
    {
        $userData = $request->getSession()->get('oidc_user', []);
        $tokenData = $request->getSession()->get('oidc_token', []);

        return $this->render('dashboard/dashboard.html.twig', [
            'user' => $userData,
            'token' => $tokenData,
        ]);
    }
}
