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
        $data = $marvelApi->getCharacters(2, 900);
        return $this->json($data);
    }

    #[Route('/marvel/comics', name: 'marvel_comics')]
    public function comics(MarvelApiService $marvelApi): JsonResponse
    {
        $data = $marvelApi->getComics(2, 9);
        return $this->json($data);
    }

    #[Route('/marvel/creators', name: 'marvel_creators')]
    public function creators(MarvelApiService $marvelApi): JsonResponse
    {
        $data = $marvelApi->getCreators(2, 9);
        return $this->json($data);
    }

    #[Route('/marvel/series', name: 'marvel_series')]
    public function series(MarvelApiService $marvelApi): JsonResponse
    {
        $data = $marvelApi->getSeries(10, 10);
        return $this->json($data);
    }
}
