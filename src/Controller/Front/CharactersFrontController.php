<?php

namespace App\Controller\Front;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * CharactersFrontController
 *
 * Handles front-end routes for listing, searching, and viewing individual Characters.
 * Uses an HTTP client to fetch data from the API endpoints and renders Twig templates.
 */
final class CharactersFrontController extends AbstractController
{
    /**
     * Constructor
     *
     * @param HttpClientInterface $client HTTP client for calling API endpoints
     */
    public function __construct(private readonly HttpClientInterface $client)
    {
    }

    /**
     * List all characters
     *
     * Fetches the list of characters from the API, sorts them alphabetically by name,
     * and renders the index template.
     *
     * @Route("/characters", name="front_characters")
     *
     * @param Request $request Symfony HTTP request object
     * @return Response The rendered HTML response
     */
    public function allCharacters(Request $request): Response
    {
        $baseUrl = $request->getSchemeAndHttpHost();

        $response = $this->client->request('GET', $baseUrl . '/api/characters');
        $data = $response->toArray();

        $characters = $data['member'] ?? [];
        usort($characters, fn($a, $b) => strnatcasecmp($a['name'], $b['name']));

        return $this->render('characters/index.html.twig', [
            'characters' => $characters,
        ]);
    }

    /**
     * Search for characters by name
     *
     * Accepts a query parameter `name` and fetches matching characters from the API.
     * If no name is provided, redirects to the main character list.
     *
     * @Route("/characters/search", name="front_characters_search", methods={"GET"})
     *
     * @param Request $request Symfony HTTP request object
     * @return Response The rendered HTML snippet with search results
     */
    public function search(Request $request): Response
    {
        $name = $request->query->get('name', '');
        if (empty($name)) {
            return $this->redirectToRoute('front_characters');
        }

        $baseUrl = $request->getSchemeAndHttpHost();
        $response = $this->client->request(
            'GET',
            $baseUrl . '/api/searchCharactersByName?name=' . urlencode($name)
        );

        $charactersData = $response->toArray();

        return $this->render('characters/_list.html.twig', [
            'characters' => $charactersData['member'] ?? [],
        ]);
    }

    /**
     * Show character details
     *
     * Fetches a single character by ID from the API and renders the detail page.
     * If the ID is empty, redirects back to the character list.
     *
     * @Route("/characters/{id}-{slug}", name="character_details", methods={"GET"})
     *
     * @param Request $request Symfony HTTP request object
     * @param string $id Marvel ID of the character
     * @return Response The rendered HTML response for the character
     */
    public function characterDetails(Request $request, string $id): Response
    {
        if (empty($id)) {
            return $this->redirectToRoute('front_characters');
        }

        $baseUrl = $request->getSchemeAndHttpHost();
        $response = $this->client->request('GET', $baseUrl . '/api/characters/' . urlencode($id));
        $characterDetailsData = $response->toArray();

        return $this->render('characters/character_details.html.twig', [
            'character' => $characterDetailsData ?? [],
        ]);
    }
}
