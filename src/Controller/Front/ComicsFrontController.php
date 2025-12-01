<?php

namespace App\Controller\Front;

use App\Service\ExtractCreatorService;
use App\Service\ExtractVariantService;
use App\Service\PagingService;
use App\Service\CacheService;
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
 * Class ComicsFrontController
 *
 * Handles the frontend routes for Marvel comics pages.
 *
 * Responsibilities:
 * - Listing comics with pagination
 * - Searching comics by title
 * - Displaying individual comic details
 *
 * This controller fetches data from internal API endpoints using HttpClientInterface
 * and leverages CacheService to improve performance.
 *
 * API Platform internal metadata keys may also be cached automatically.
 *
 * @package App\Controller\Front
 */
final class ComicsFrontController extends AbstractController
{
    /**
     * ComicsFrontController constructor.
     *
     * @param HttpClientInterface $client Http client used for API requests
     * @param ExtractCreatorService $extractCreatorsService Service to enrich comic creators
     * @param ExtractVariantService $extractVariantsService Service to enrich comic variants
     * @param PagingService $pagingService Service to generate pagination data
     * @param CacheService $cacheService Service to handle caching of API responses
     */
    public function __construct(
        private readonly HttpClientInterface   $client,
        private readonly ExtractCreatorService $extractCreatorsService,
        private readonly ExtractVariantService $extractVariantsService,
        private readonly PagingService         $pagingService,
        private readonly CacheService          $cacheService
    ) {}

    /**
     * Displays a paginated list of comics.
     *
     * Retrieves the comic list from `/api/comics` endpoint.
     * Uses CacheService to store page data for 1 hour to improve performance.
     * If the requested page exceeds the total number of pages, redirects to the first page.
     *
     * @param Request $request The current HTTP request object
     * @return Response Rendered HTML response containing the comics list and pagination data
     *
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface|InvalidArgumentException
     */
    #[Route('/comics', name: 'front_comics')]
    public function allComics(Request $request): Response
    {
        $baseUrl = $request->getSchemeAndHttpHost();
        $page = max(1, (int)$request->query->get('page', 1));
        $cacheKey = 'comics_page_' . $page;

        $datas = $this->cacheService->get($cacheKey, function () use ($baseUrl, $page) {
            $response = $this->client->request('GET', $baseUrl . '/api/comics', [
                'query' => ['page' => $page]
            ]);
            return $response->toArray()['member'] ?? [];
        });

        $totalItems = $datas[0];
        $itemsPerPage = $datas[1];
        $comics = $datas[2];

        $paging = $this->pagingService->paging($totalItems, $page, $itemsPerPage, 8);

        if ($page > $paging['totalPages']) {
            return $this->redirectToRoute('front_comics');
        }

        return $this->render('comics/index.html.twig', [
            'comics' => $comics,
            'currentPage' => $page,
            'startPage' => $paging['startPage'],
            'endPage' => $paging['endPage'],
            'totalPages' => $paging['totalPages'],
        ]);
    }

    /**
     * Searches for comics by title.
     *
     * Reads the `title` query parameter and calls `/api/searchComicsByTitle`.
     * Uses CacheService to store search results for 5 minutes.
     * Clips results for the requested page using PagingService.
     *
     * @param Request $request Symfony HTTP request object
     * @return Response Rendered HTML response with search results
     *
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface|InvalidArgumentException
     */
    #[Route('/comics/search', name: 'front_comics_search', methods: ['GET'])]
    public function search(Request $request): Response
    {
        $title = $request->query->get('title', '');
        if (empty($title)) {
            return $this->redirectToRoute('front_comics');
        }

        $page = max(1, (int)$request->query->get('page', 1));
        $baseUrl = $request->getSchemeAndHttpHost();
        $cacheKey = 'search_comics_' . $title . '_page_' . $page;

        $datas = $this->cacheService->get($cacheKey, function () use ($baseUrl, $title, $page) {
            $response = $this->client->request('GET', $baseUrl . '/api/searchComicsByTitle', [
                'query' => ['title' => urlencode($title), 'page' => $page]
            ]);
            return $response->toArray()['member'];
        }, 300);

        $comicsResearch = $datas[1];
        $itemsPerPage = $datas[0];
        $offset = ($page - 1) * $itemsPerPage;
        $comics = array_slice($comicsResearch, $offset, $itemsPerPage);
        $totalItems = count($comicsResearch);

        $paging = $this->pagingService->paging($totalItems, $page, $itemsPerPage, 8);

        return $this->render('comics/_list.html.twig', [
            'comics' => $comics,
            'currentPage' => $page,
            'totalPages' => $paging['totalPages'],
            'startPage' => $paging['startPage'],
            'endPage' => $paging['endPage'],
            'routeName' => 'front_comics_search',
            'searchTitle' => $title,
        ]);
    }

    /**
     * Displays details of a single comic.
     *
     * Fetches the comic from `/api/comics/{id}` and enriches it with creators and variants.
     * Redirects to the comic list if ID is empty.
     *
     * @param Request $request Symfony HTTP request object
     * @param string $id Marvel ID of the comic
     * @return Response Rendered HTML response for the comic details page
     *
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    #[Route('/comics/{id}-{slug}', name: 'comic_details', methods: ['GET'])]
    public function comicDetails(Request $request, string $id): Response
    {
        if (empty($id)) {
            return $this->redirectToRoute('front_comics');
        }

        $baseUrl = $request->getSchemeAndHttpHost();
        $response = $this->client->request('GET', $baseUrl . '/api/comics/' . urlencode($id));

        $comicDetailsData = $response->toArray();
        $comicDetailsData = $this->extractCreatorsService->enrichCreators($comicDetailsData, $baseUrl);
        $comicDetailsData = $this->extractVariantsService->enrichVariants($comicDetailsData, $baseUrl);

        return $this->render('comics/comic_details.html.twig', [
            'comic' => $comicDetailsData,
        ]);
    }
}
