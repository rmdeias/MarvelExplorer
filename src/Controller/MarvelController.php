<?php

namespace App\Controller;
use App\Service\MarvelApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class MarvelController extends AbstractController
{
    #[Route('/marvel/characters', name: 'marvel_characters')]
    public function characters(MarvelApiService $marvelApi): JsonResponse
    {
        $data = $marvelApi->getCharacters(5);
        return $this->json($data);
    }
}
