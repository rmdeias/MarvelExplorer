<?php

namespace App\Controller\Front;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CreatorsFrontController extends AbstractController
{
    #[Route('/creators', name: 'app_creators')]
    public function index(): Response
    {
        return $this->render('creators/index.html.twig', [
            'controller_name' => 'CreatorsController',
        ]);
    }
}
