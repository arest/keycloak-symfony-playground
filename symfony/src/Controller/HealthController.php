<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HealthController
{
    #[Route('/health', name: 'health')]
    public function __invoke(): Response
    {
        return new Response('OK', Response::HTTP_OK);
    }
}
