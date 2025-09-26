<?php

namespace App\Controller\Front;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;


final class ComicsFrontController extends AbstractController
{
    public function __construct(private HttpClientInterface $client)
    {
    }

    #[Route('/comics', name: 'front_comics')]
    public function index(Request $request): Response
    {
        // Appel API pour le top récents
        $baseUrl = $request->getSchemeAndHttpHost(); // http://127.0.0.1:8000
        $response = $this->client->request('GET', $baseUrl . '/api/topRecentComics');

        $topComics = $response->toArray();

        return $this->render('comics/index.html.twig', [
            'comics' => $topComics['member'],
        ]);

    }

    #[Route('/comics/search', name: 'front_comics_search', methods: ['GET'])]
    public function search(Request $request): Response
    {
        $title = $request->query->get('title', '');
        if (empty($title)) {
            return $this->redirectToRoute('front_comics');
        }

        // Appel API pour la recherche
        $baseUrl = $request->getSchemeAndHttpHost(); // http://127.0.0.1:8000
        $response = $this->client->request('GET', $baseUrl . '/api/searchComicsByTitle?title=' . urlencode($title));

        $comicsData = $response->toArray();

        return $this->render('comics/_list.html.twig', [
            'comics' => $comicsData['member'] ?? [],
        ]);
    }


}
