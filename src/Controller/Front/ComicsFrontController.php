<?php

namespace App\Controller\Front;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class ComicsFrontController extends AbstractController
{
    public function __construct(private HttpClientInterface $client)
    {
    }

    #[Route('/comics', name: 'front_comics')]
    public function index(): Response
    {
        // Appel API REST interne
        $apiUrl = 'http://127.0.0.1:8000/api/comics?limit=10&sort=recent';

        $response = $this->client->request('GET', $apiUrl);
        $comics = $response->toArray()['data'] ?? [];

        return $this->render('comics/index.html.twig', [
            'comics' => $comics,
        ]);
    }
}
