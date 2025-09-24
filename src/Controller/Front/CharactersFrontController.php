<?php

namespace App\Controller\Front;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CharactersFrontController extends AbstractController
{
    #[Route('/characters', name: 'app_characters')]
    public function index(): Response
    {
        return $this->render('characters/index.html.twig', [
            'controller_name' => 'CharactersController',
        ]);
    }
}
