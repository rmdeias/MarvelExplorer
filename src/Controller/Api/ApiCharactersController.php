<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ApiCharactersController extends AbstractController
{
    #[Route('/api/characters', name: 'app_api_characters')]
    public function index(): Response
    {
        return $this->render('api_characters/index.html.twig', [
            'controller_name' => 'ApiCharactersController',
        ]);
    }
}
