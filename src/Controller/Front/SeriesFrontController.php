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

final class SeriesFrontController extends AbstractController
{
    /**
     * ComicsFrontController constructor.
     *
     * @param HttpClientInterface $client Http client used to call internal API endpoints
     */
    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly ExtractCreatorService $extractCreatorsService)
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
    public function index(): Response
    {
        return $this->render('series/index.html.twig', []);
    }

    /**
     * Comic page details.
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

        $serieDetailsData = $this->extractCreatorsService->enrichCreators($serieDetailsData, $baseUrl);



        return $this->render('series/serie_details.html.twig', [
            'serie' => $serieDetailsData ?? [],
        ]);
    }
}
