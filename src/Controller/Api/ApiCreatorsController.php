<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ApiCreatorsController extends AbstractController
{
    #[Route('/api/creators', name: 'app_api_creators')]
    public function index(): Response
    {
        return $this->render('api_creators/index.html.twig', [
            'controller_name' => 'ApiCreatorsController',
        ]);
    }
}
