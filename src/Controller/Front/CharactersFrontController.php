<?php

namespace App\Controller\Front;


use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Service\PagingService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * CharactersFrontController
 *
 * Handles front-end routes for listing, searching, and viewing individual Characters.
 * Uses an HTTP client to fetch data from the API endpoints and renders Twig templates.
 */
final class CharactersFrontController extends AbstractController
{
    /**
     * Constructor
     *
     * @param HttpClientInterface $client HTTP client for calling API endpoints
     */
    public function __construct(private readonly HttpClientInterface $client,
                                private readonly PagingService       $pagingService)
    {
    }

    /**
     * List all characters
     *
     * Fetches the list of characters from the API, sorts them alphabetically by name,
     * and renders the index template.
     *
     * @param Request $request Symfony HTTP request object
     * @return Response The rendered HTML response
     */
    #[Route('/characters', name: 'front_characters')]
    public function allCharacters(Request $request): Response
    {
        $baseUrl = $request->getSchemeAndHttpHost();
        $page = max(1, (int)$request->query->get('page', 1));

        $response = $this->client->request('GET', $baseUrl . '/api/characters', [
            'query' => ['page' => $page]
        ]);
        $datas = $response->toArray();

        $totalItems = $datas['member'][0];
        $itemsPerPage = $datas['member'][1];
        $characters = $datas['member'][2];

        $paging = $this->pagingService->paging($totalItems, $page, $itemsPerPage, 8);

        if ($page > $paging['totalPages']) {
            return $this->redirectToRoute('front_characters');
        }

        return $this->render('characters/index.html.twig', [
            'characters' => $characters,
            'currentPage' => $page,
            'startPage' => $paging['startPage'],
            'endPage' => $paging['endPage'],
            'totalPages' => $paging['totalPages'],
        ]);
    }

    /**
     * Search for characters by name
     *
     * Accepts a query parameter `name` and fetches matching characters from the API.
     * If no name is provided, redirects to the main character list.
     *
     * @param Request $request Symfony HTTP request object
     * @return Response The rendered HTML snippet with search results
     */
    #[Route('/characters/search', name: 'front_characters_search', methods: ['GET'])]
    public function search(Request $request): Response
    {
        $name = $request->query->get('name', '');
        if (empty($name)) {
            return $this->redirectToRoute('front_characters');
        }

        $page = max(1, (int)$request->query->get('page', 1));

        $baseUrl = $request->getSchemeAndHttpHost();
        $response = $this->client->request(
            'GET',
            $baseUrl . '/api/searchCharactersByName', [
            'query' => [
                'name' => urlencode($name),
                'page' => $page,
            ]
        ]);

        $datas = $response->toArray();
        $charactersResearch = $datas['member'][1] ?? [];
        $itemsPerPage = $datas['member'][0];

        //Clip results for the current page
        $offset = ($page - 1) * $itemsPerPage;
        $characters = array_slice($charactersResearch, $offset, $itemsPerPage);
        $totalItems = count($charactersResearch);

        $paging = $this->pagingService->paging($totalItems, $page, $itemsPerPage, 8);

        return $this->render('characters/_list.html.twig', [
            'characters' => $characters,
            'currentPage' => $page,
            'startPage' => $paging['$startPage'],
            'endPage' => $paging['$endPage'],
            'totalPages' => $paging['$totalPages'],
            'routeName' => 'front_characters_search',
            'searchTitle' => $name,
        ]);
    }

    /**
     * Show character details
     *
     * Fetches a single character by ID from the API and renders the detail page.
     * If the ID is empty, redirects back to the character list.
     *
     *
     * @param Request $request Symfony HTTP request object
     * @param string $id Marvel ID of the character
     * @return Response The rendered HTML response for the character
     */
    #[Route('/characters/{id}-{slug}', name: 'character_details', methods: ['GET'])]
    public function characterDetails(Request $request, string $id): Response
    {
        if (empty($id)) {
            return $this->redirectToRoute('front_characters');
        }

        $baseUrl = $request->getSchemeAndHttpHost();
        $response = $this->client->request('GET', $baseUrl . '/api/characters/' . urlencode($id));
        $characterDetailsData = $response->toArray();

        return $this->render('characters/character_details.html.twig', [
            'character' => $characterDetailsData ?? [],
        ]);
    }
}
