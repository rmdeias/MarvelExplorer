<?php

namespace App\Controller\Front;

use App\Service\ExtractCreatorService;
use App\Service\ExtractVariantService;
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

/**
 * Class ComicsFrontController
 *
 * Controller responsible for handling frontend comic pages.
 *
 * This controller provides:
 * - Listing  comics
 * - Searching comics by title
 * - Listing  comics by marvelId
 *
 * It communicates with the internal API using HttpClientInterface
 * to fetch comic data and passes it to Twig templates for rendering.
 *
 * All API requests may throw HttpClient exceptions.
 */
final class ComicsFrontController extends AbstractController
{
    /**
     * ComicsFrontController constructor.
     *
     * @param HttpClientInterface $client Http client used to call internal API endpoints
     */
    public function __construct(
        private readonly HttpClientInterface   $client,
        private readonly ExtractCreatorService $extractCreatorsService,
        private readonly ExtractVariantService $extractVariantsService,
        private readonly PagingService         $pagingService)
    {
    }

    /**
     * Displays a paginated list of comics.
     *
     * This controller fetches comics from the API Platform endpoint `/api/comics`
     * using pagination from entity. It also retrieves the total number of
     * comics via the `/api/countFilteredComics` endpoint to calculate the number of pages.
     *
     * If the current page exceeds the total number of pages, the user is redirected
     * to the first page. The pagination bar displays up to 10 pages at a time.
     *
     * @param Request $request The current HTTP request (used to get the page number)
     *
     * @return Response The rendered HTML response containing the comics list and pagination
     *
     * @throws TransportExceptionInterface
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     *
     */
    #[Route('/comics', name: 'front_comics')]
    public function allComics(Request $request): Response
    {
        $baseUrl = $request->getSchemeAndHttpHost();
        $page = max(1, (int)$request->query->get('page', 1));

        // Récupère la page N (100 comics filtrés par page via DataProvider)
        $response = $this->client->request('GET', $baseUrl . '/api/comics', [
            'query' => ['page' => $page]
        ]);

        $datas = $response->toArray()['member'] ?? [];
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
     * Reads the 'title' query parameter from the request, calls the internal API
     * endpoint '/api/searchComicsByTitle', and renders the results in the
     * 'comics/_list.html.twig' template.
     * If the title is empty, redirects to the main comics page.
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
    #[Route('/comics/search', name: 'front_comics_search', methods: ['GET'])]
    public function search(Request $request): Response
    {
        $title = $request->query->get('title', '');
        if (empty($title)) {
            return $this->redirectToRoute('front_comics');
        }

        $page = max(1, (int)$request->query->get('page', 1));


        $baseUrl = $request->getSchemeAndHttpHost();

        $response = $this->client->request('GET', $baseUrl . '/api/searchComicsByTitle', [
            'query' => [
                'title' => urlencode($title),
                'page' => $page,
            ]
        ]);

        $datas = $response->toArray();
        $comicsResearch = $datas['member'][1] ?? [];
        $itemsPerPage = $datas['member'][0];

        //Clip results for the current page
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
     * Comic page details.
     *
     * Reads the 'id' query parameter from the request, calls the internal API
     * endpoint '/api/comics/{id}-{slug}', and renders the results in the
     * 'comics/comic_details.html.twig' template.
     * If the id is empty, redirects to the main comics page.
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
