<?php

namespace App\Controller\Front;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class CreatorsFrontController extends AbstractController
{
    /**
     * ComicsFrontController constructor.
     *
     * @param HttpClientInterface $client Http client used to call internal API endpoints
     */
    public function __construct(
        private readonly HttpClientInterface $client,
    )
    {
    }

    /**
     * Displays a paginated list of creators.
     *
     * This controller fetches creators from the API Platform endpoint `/api/creators`
     *
     * @param Request $request The current HTTP request
     *
     * @return Response The rendered HTML response containing the creators list and pagination
     *
     * @throws TransportExceptionInterface
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     *
     */
    #[Route('/creators', name: 'front_creators')]
    public function allCreators(Request $request): Response
    {
        $baseUrl = $request->getSchemeAndHttpHost();
        $response = $this->client->request('GET', $baseUrl . '/api/creators');
        $creators = $response->toArray()['member'];

        $grouped = [];
        foreach ($creators as $creator) {
            // if lastname is empty take fullName
            $nameToUse = !empty($creator['lastName']) ? $creator['lastName'] : $creator['fullName'];

            //take first letter in uppercase
            $letter = mb_strtoupper(mb_substr($nameToUse, 0, 1));

            $grouped[$letter][] = $creator;
        }

        ksort($grouped);
        $alphabet = range('A', 'Z');


        return $this->render('creators/index.html.twig', [
            'groupedAuthors' => $grouped,
            'alphabet' => $alphabet,

        ]);
    }

    /**
     * Serie page details.
     *
     * Reads the 'id' query parameter from the request, calls the internal API
     * endpoint '/api/creators/{id}-{slug}', and renders the results in the
     * 'creators/creator_details.html.twig' template.
     * If the id is empty, redirects to the main creators page.
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
    #[Route('/creators/{id}-{slug}', name: 'creator_details', methods: ['GET'])]
    public function creatorDetails(Request $request, string $id): Response
    {

        if (empty($id)) {
            return $this->redirectToRoute('front_creators');
        }

        $baseUrl = $request->getSchemeAndHttpHost();
        $response = $this->client->request('GET', $baseUrl . '/api/creators/' . urlencode($id));
        $creatorDetailsData = $response->toArray();
        $creatorFullName = $creatorDetailsData['fullName'];

        $response = $this->client->request('GET', $baseUrl . '/api/seriesByCreator/' . urlencode($id));
        $seriesByCreator['series'] = $response->toArray()['member'][0];
        $seriesByCreator['creator'] = $creatorFullName;


        return $this->render('creators/creator_details.html.twig', [
            'seriesByCreator' => $seriesByCreator,
        ]);
    }
}
