<?php

namespace App\Controller\Front;

use App\Service\CacheService;
use App\Service\PagingService;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Class CharactersFrontController
 *
 * Handles front-end routes for displaying, searching, and viewing individual Marvel characters.
 *
 * This controller communicates with internal API endpoints using HttpClientInterface and caches
 * results for better performance using CacheService. Pagination is handled via PagingService.
 */
final class CharactersFrontController extends AbstractController
{
    /**
     * CharactersFrontController constructor.
     *
     * @param HttpClientInterface $client HTTP client used to fetch data from API endpoints
     * @param PagingService $pagingService Service responsible for pagination logic
     * @param CacheService $cacheService Service for caching API responses
     */
    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly PagingService       $pagingService,
        private readonly CacheService        $cacheService
    )
    {
    }

    /**
     * Displays a paginated list of characters.
     *
     * Fetches characters from the API endpoint `/api/characters` and caches the results for 1 hour.
     * Characters are displayed alphabetically, and pagination ensures the current page is within valid range.
     *
     * @param Request $request Symfony HTTP request object
     * @return Response Rendered HTML response containing the list of characters and pagination
     *
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface|InvalidArgumentException
     */
    #[Route('/characters', name: 'front_characters')]
    public function allCharacters(Request $request): Response
    {
        $baseUrl = $request->getSchemeAndHttpHost();
        $page = max(1, (int)$request->query->get('page', 1));

        $cacheKey = 'characters_page_' . $page;

        $datas = $this->cacheService->get($cacheKey, function () use ($baseUrl, $page) {
            $response = $this->client->request('GET', $baseUrl . '/api/characters', [
                'query' => ['page' => $page],
            ]);
            return $response->toArray()['member'] ?? [];
        }); // TTL 1 hour

        $totalItems = $datas[0];
        $itemsPerPage = $datas[1];
        $characters = $datas[2];

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
     * Searches for characters by name.
     *
     * Reads the 'name' query parameter from the request, calls the internal API endpoint
     * `/api/searchCharactersByName`, caches the results for 5 minutes, and renders them.
     * If the name parameter is empty, redirects to the main characters page.
     *
     * @param Request $request Symfony HTTP request object
     * @return Response Rendered HTML snippet containing search results and pagination
     *
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface|InvalidArgumentException
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
        $cacheKey = 'search_characters_' . $name . '_page_' . $page;

        $datas = $this->cacheService->get($cacheKey, function () use ($baseUrl, $name, $page) {
            $response = $this->client->request('GET', $baseUrl . '/api/searchCharactersByName', [
                'query' => [
                    'name' => urlencode($name),
                    'page' => $page,
                ]
            ]);
            return $response->toArray()['member'];
        }, 300);  // TTL 5 minutes

        $charactersResearch = $datas[1] ?? [];
        $itemsPerPage = $datas[0];

        $offset = ($page - 1) * $itemsPerPage;
        $characters = array_slice($charactersResearch, $offset, $itemsPerPage);
        $totalItems = count($charactersResearch);

        $paging = $this->pagingService->paging($totalItems, $page, $itemsPerPage, 8);

        return $this->render('characters/_list.html.twig', [
            'characters' => $characters,
            'currentPage' => $page,
            'startPage' => $paging['startPage'],
            'endPage' => $paging['endPage'],
            'totalPages' => $paging['totalPages'],
            'routeName' => 'front_characters_search',
            'searchTitle' => $name,
        ]);
    }

    /**
     * Displays details for a single character.
     *
     * Fetches character details from the API endpoint `/api/characters/{id}` and renders the detail page.
     * If the ID is empty, redirects back to the characters list.
     *
     * @param Request $request Symfony HTTP request object
     * @param string $id Marvel ID of the character
     * @return Response Rendered HTML response with the character details
     *
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
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
            'character' => $characterDetailsData,
        ]);
    }
}
