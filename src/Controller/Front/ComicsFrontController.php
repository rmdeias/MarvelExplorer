<?php

namespace App\Controller\Front;

use App\Service\ExtractCreatorService;
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
 * - Listing of recent comics (top comics)
 * - Searching comics by title
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
    public function __construct(private readonly HttpClientInterface $client, private readonly ExtractCreatorService $extractCreatorsService)
    {
    }

    /**
     * Displays the list of top recent comics.
     *
     * Calls the internal API endpoint '/api/topRecentComics' and passes
     * the result to the 'comics/index.html.twig' template.
     *
     * @param Request $request HTTP request object
     * @return Response Rendered HTML response with top comics
     *
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    #[Route('/', name: 'front_comics')]
    public function index(Request $request): Response
    {
        $baseUrl = $request->getSchemeAndHttpHost();
        $response = $this->client->request('GET', $baseUrl . '/api/topRecentComics');

        $topComics = $response->toArray();

        return $this->render('comics/index.html.twig', [
            'comics' => $topComics['member'],
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

        $baseUrl = $request->getSchemeAndHttpHost();
        $response = $this->client->request('GET', $baseUrl . '/api/searchComicsByTitle?title=' . urlencode($title));

        $comicsData = $response->toArray();

        return $this->render('comics/_list.html.twig', [
            'comics' => $comicsData['member'] ?? [],
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

        return $this->render('comics/comic_details.html.twig', [
            'comicDetails' => $comicsDetailsData ?? [],
        ]);
    }
}
