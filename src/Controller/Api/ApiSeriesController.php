<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ApiSeriesController extends AbstractController
{
    #[Route('/api/series', name: 'app_api_series')]
    public function index(): Response
    {
        return $this->render('api_series/index.html.twig', [
            'controller_name' => 'ApiSeriesController',
        ]);
    }
}
