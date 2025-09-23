<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ComicController extends AbstractController
{
    #[Route('/comic', name: 'app_comic')]
    public function index(): Response
    {
        return $this->render('comic/index.html.twig', [
            'controller_name' => 'ComicController',
        ]);
    }
}
