<?php

namespace App\Controller\Front;

use App\Service\CacheService;
use App\Service\ExtractCreatorService;
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
 * Class SeriesFrontController
 *
 * Handles frontend operations related to comic series.
 *
 * Provides functionalities to:
 * - Display paginated series lists
 * - Search series by title
 * - Display detailed series pages
 *
 * Communicates with the internal API using HttpClientInterface and caches results via CacheService.
 */
final class SeriesFrontController extends AbstractController
{
    /**
     * SeriesFrontController constructor.
     *
     * @param HttpClientInterface $client Http client for internal API requests
     * @param ExtractCreatorService $extractCreatorsService Service to enrich creator information
     * @param PagingService $pagingService Service to calculate pagination
     * @param CacheService $cacheService Service for caching API results
     */
    public function __construct(
        private readonly HttpClientInterface   $client,
        private readonly ExtractCreatorService $extractCreatorsService,
        private readonly PagingService         $pagingService,
        private readonly CacheService          $cacheService
    )
    {
    }

    /**
     * Displays a paginated list of comic series.
     *
     * Fetches series from the API endpoint `/api/series` and caches the results for 1 hour.
     * Calculates pagination and ensures the requested page is within valid range.
     *
     * @param Request $request Symfony HTTP request object
     * @return Response Rendered HTML response containing series list and pagination
     *
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface|InvalidArgumentException
     */
    #[Route('/series', name: 'front_series')]
    public function allSeries(Request $request): Response
    {
        $baseUrl = $request->getSchemeAndHttpHost();
        $page = max(1, (int)$request->query->get('page', 1));

        $cacheKey = 'series_page_' . $page;

        $datas = $this->cacheService->get($cacheKey, function () use ($baseUrl, $page) {
            $response = $this->client->request('GET', $baseUrl . '/api/series', [
                'query' => ['page' => $page]
            ]);
            return $response->toArray()['member'] ?? [];
        }); // TTL 1 hour

        $totalItems = $datas[0];
        $itemsPerPage = $datas[1];
        $series = $datas[2];

        $paging = $this->pagingService->paging($totalItems, $page, $itemsPerPage, 8);

        if ($page > $paging['totalPages']) {
            return $this->redirectToRoute('front_series');
        }

        return $this->render('series/index.html.twig', [
            'series' => $series,
            'currentPage' => $page,
            'startPage' => $paging['startPage'],
            'endPage' => $paging['endPage'],
            'totalPages' => $paging['totalPages'],
        ]);
    }

    /**
     * Searches for series by title.
     *
     * Fetches results from `/api/searchSeriesByTitle` endpoint and caches them for 5 minutes.
     * Paginates the search results for display.
     *
     * @param Request $request Symfony HTTP request object
     * @return Response Rendered HTML response with search results
     *
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface|InvalidArgumentException
     */
    #[Route('/series/search', name: 'front_series_search', methods: ['GET'])]
    public function search(Request $request): Response
    {
        $title = $request->query->get('title', '');
        if (empty($title)) {
            return $this->redirectToRoute('front_series');
        }

        $page = max(1, (int)$request->query->get('page', 1));
        $baseUrl = $request->getSchemeAndHttpHost();

        $cacheKey = 'search_series_' . $title . '_page_' . $page;

        $datas = $this->cacheService->get($cacheKey, function () use ($baseUrl, $title, $page) {
            $response = $this->client->request('GET', $baseUrl . '/api/searchSeriesByTitle', [
                'query' => [
                    'title' => urlencode($title),
                    'page' => $page,
                ]
            ]);
            return $response->toArray()['member'];
        }, 300); // TTL 5 minutes

        $itemsPerPage = $datas[0];
        $seriesResearch = $datas[1];

        $offset = ($page - 1) * $itemsPerPage;
        $series = array_slice($seriesResearch, $offset, $itemsPerPage);
        $totalItems = count($seriesResearch);

        $paging = $this->pagingService->paging($totalItems, $page, $itemsPerPage, 8);

        return $this->render('series/_list.html.twig', [
            'series' => $series,
            'currentPage' => $page,
            'startPage' => $paging['startPage'],
            'endPage' => $paging['endPage'],
            'totalPages' => $paging['totalPages'],
            'routeName' => 'front_series_search',
            'searchTitle' => $title,
        ]);
    }

    /**
     * Displays detailed information for a single series.
     *
     * Fetches series details from `/api/series/{id}` endpoint and enriches creator information.
     * Sorts associated comics alphabetically by title.
     *
     * @param Request $request Symfony HTTP request object
     * @param string $id Marvel ID of the series
     * @return Response Rendered HTML response for the series details
     *
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    #[Route('/series/{id}-{slug}', name: 'serie_details', methods: ['GET'])]
    public function serieDetails(Request $request, string $id): Response
    {
        if (empty($id)) {
            return $this->redirectToRoute('front_series');
        }

        $baseUrl = $request->getSchemeAndHttpHost();
        $response = $this->client->request('GET', $baseUrl . '/api/series/' . urlencode($id));
        $serieDetailsData = $response->toArray();

        // Sort comics alphabetically
        usort($serieDetailsData['comics'], fn($a, $b) => strnatcasecmp($a['title'], $b['title']));

        $serieDetailsData = $this->extractCreatorsService->enrichCreators($serieDetailsData, $baseUrl);

        return $this->render('series/serie_details.html.twig', [
            'serie' => $serieDetailsData,
        ]);
    }
}
