<?php

namespace App\Controller\Front;

use App\Service\ExtractCreatorService;
use App\Service\ExtractVariantService;
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
        private readonly ExtractVariantService $extractVariantsService)
    {
    }

    /**
     * Displays a paginated list of comics.
     *
     * This controller fetches comics from the API Platform endpoint `/api/comics`
     * using pagination (100 items per page). It also retrieves the total number of
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
        $comics = $datas[1];

        $totalPages = ceil($totalItems / 100);

        if ($page > $totalPages) {
            return $this->redirectToRoute('front_comics');
        }

        $pageRange = 10;
        $startPage = max(1, $page - intval($pageRange / 2));
        $endPage = min($totalPages, $startPage + $pageRange - 1);


        return $this->render('comics/index.html.twig', [
            'comics' => $comics,
            'currentPage' => $page,
            'startPage' => $startPage,
            'endPage' => $endPage,
            'totalPages' => $totalPages,
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
        $perPage = 100;

        $baseUrl = $request->getSchemeAndHttpHost();

        // Appel API qui renvoie tous les résultats correspondants à la recherche
        $response = $this->client->request('GET', $baseUrl . '/api/searchComicsByTitle', [
            'query' => [
                'title' => $title,
                'page' => 1,        // on récupère tout pour découper côté PHP
            ]
        ]);

        $data = $response->toArray();
        $comicsResearch = $data['member'] ?? [];

        // Découper les résultats pour la page actuelle
        $offset = ($page - 1) * $perPage;
        $comics = array_slice($comicsResearch, $offset, $perPage);

        $totalItems = count($comicsResearch);
        $totalPages = ceil($totalItems / $perPage);

        $pageRange = 10;
        $startPage = max(1, $page - intval($pageRange / 2));
        $endPage = min($totalPages, $startPage + $pageRange - 1);

        return $this->render('comics/_list.html.twig', [
            'comics' => $comics,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'startPage' => $startPage,
            'endPage' => $endPage,
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
            'comic' => $comicDetailsData ?? [],
        ]);
    }
}
