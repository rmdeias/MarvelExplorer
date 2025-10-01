<?php

namespace App\Controller\Front;

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

final class HomeFrontController extends AbstractController
{
    public function __construct(private readonly HttpClientInterface $client)
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
    #[Route('/', name: 'front_home')]
    public function index(Request $request): Response
    {
        $baseUrl = $request->getSchemeAndHttpHost();
        $response = $this->client->request('GET', $baseUrl . '/api/topRecentComics');

        $topComics = $response->toArray();

        return $this->render('home/index.html.twig', [
            'comics' => $topComics['member'],
        ]);
    }
}
