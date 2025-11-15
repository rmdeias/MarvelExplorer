<?php

namespace App\Controller\Front;

use App\Service\ExtractCreatorService;
use App\Service\PagingService;
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

final class SeriesFrontController extends AbstractController
{
    /**
     * ComicsFrontController constructor.
     *
     * @param HttpClientInterface $client Http client used to call internal API endpoints
     */
    public function __construct(
        private readonly HttpClientInterface   $client,
        private readonly ExtractCreatorService $extractCreatorsService,
        private readonly PagingService         $pagingService)
    {
    }

    /**
     * Displays the list of top recent series.
     *
     * Calls the internal API endpoint '/api/topRecentComics' and passes
     * the result to the 'series/index.html.twig' template.
     *
     * @param Request $request HTTP request object
     * @return Response Rendered HTML response with top series
     *
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    #[Route('/series', name: 'front_series')]
    public function index(Request $request): Response
    {
        $baseUrl = $request->getSchemeAndHttpHost();
        $page = max(1, (int)$request->query->get('page', 1));

        $response = $this->client->request('GET', $baseUrl . '/api/series', [
            'query' => ['page' => $page]
        ]);
        $datas = $response->toArray();

        $totalItems = $datas['member'][0];
        $itemsPerPage = $datas['member'][1];
        $series = $datas['member'][2];

        $paging = $this->pagingService->paging($totalItems, $page, $itemsPerPage, 8);

        if ($page > $paging['totalPages']) {
            return $this->redirectToRoute('front_series');
        }


        return $this->render('series/index.html.twig', [
            'series' => $series,
            'currentPage' => $page,
            'startPage' => $paging['startPage'],
            'endPage' => $paging['endPage'],
            'totalPages' => $paging['totalPages']
        ]);
    }

    /**
     * Searches for series by title.
     *
     * Reads the 'title' query parameter from the request, calls the internal API
     * endpoint '/api/searchComicsByTitle', and renders the results in the
     * 'series/_list.html.twig' template.
     * If the title is empty, redirects to the main series page.
     *
     * @param Request $request HTTP request object
     * @return Response Rendered HTML response with search results
     *
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
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
        $response = $this->client->request('GET', $baseUrl . '/api/searchSeriesByTitle?title=' . urlencode($title), [
            'query' => [
                'title' => urlencode($title),
                'page' => $page,
            ]
        ]);

        $datas = $response->toArray();
        $itemsPerPage = $datas['member'][0];
        $seriesResearch = $datas['member'][1];

        //Clip results for the current page
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
     * Serie page details.
     *
     * Reads the 'id' query parameter from the request, calls the internal API
     * endpoint '/api/series/{id}-{slug}', and renders the results in the
     * 'series/serie_details.html.twig' template.
     * If the id is empty, redirects to the main series page.
     *
     * @param Request $request HTTP request object
     * @return Response Rendered HTML response with search results
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

        usort($serieDetailsData['comics'], fn($a, $b) => strnatcasecmp($a['title'], $b['title']));

        $serieDetailsData = $this->extractCreatorsService->enrichCreators($serieDetailsData, $baseUrl);


        return $this->render('series/serie_details.html.twig', [
            'serie' => $serieDetailsData,
        ]);
    }
}
