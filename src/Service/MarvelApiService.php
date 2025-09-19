<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\{
    DecodingExceptionInterface,
    RedirectionExceptionInterface,
    ClientExceptionInterface,
    TransportExceptionInterface,
    ServerExceptionInterface
};

/**
 * Service to interact with the Marvel API.
 * Provides methods to fetch characters, comics, creators, and series.
 */
class MarvelApiService
{
    private HttpClientInterface $client;
    private string $baseUrl = 'https://gateway.marvel.com/v1/public';
    private string $publicKey;
    private string $privateKey;

    /**
     * Constructor.
     *
     * @param HttpClientInterface $client HTTP client for API requests.
     * @param string $publicKey Marvel public API key.
     * @param string $privateKey Marvel private API key.
     */
    public function __construct(HttpClientInterface $client, string $publicKey, string $privateKey)
    {
        $this->client = $client;
        $this->publicKey = $publicKey;
        $this->privateKey = $privateKey;
    }

    /**
     * Generates authentication parameters required by the Marvel API.
     *
     * @return array{ts:int, apikey:string, hash:string} Authentication parameters.
     */
    private function generateAuthParams(): array
    {
        $ts = time();
        return [
            'ts' => $ts,
            'apikey' => $this->publicKey,
            'hash' => md5($ts . $this->privateKey . $this->publicKey),
        ];
    }

    /**
     * Performs a GET request to a Marvel API endpoint and returns the results.
     *
     * @param string $endpoint API endpoint (e.g., "characters", "comics").
     * @param int $limit Maximum number of results (Marvel max is 100).
     * @param int $offset Offset for pagination.
     * @param string|null $modifiedSince Last updated date (optional).
     *
     * @return array Decoded results from the API.
     *
     * @throws TransportExceptionInterface On network errors.
     * @throws ClientExceptionInterface On client (4xx) errors.
     * @throws RedirectionExceptionInterface On redirection errors.
     * @throws ServerExceptionInterface On server (5xx) errors.
     * @throws DecodingExceptionInterface On JSON decoding errors.
     * @throws \RuntimeException If the HTTP response code is not 200.
     */
    private function fetchData(string $endpoint, int $limit, int $offset, ?string $modifiedSince = null): array
    {
        $params = array_merge([
            'limit' => $limit,
            'offset' => $offset,
        ], $this->generateAuthParams());

        // Ajouter le paramètre modifiedSince uniquement s'il n'est pas null
        if ($modifiedSince !== null) {
            $params['modifiedSince'] = $modifiedSince;
        }

        $response = $this->client->request('GET', "{$this->baseUrl}/{$endpoint}", [
            'query' => $params,
        ]);

        if ($response->getStatusCode() !== 200) {
            throw new \RuntimeException("Marvel API error: {$response->getStatusCode()}");
        }

        return $response->toArray()['data']['results'] ?? [];
    }


    /**
     * Fetches all data from a Marvel API endpoint while automatically handling pagination.
     *
     * This method repeatedly calls `fetchData()` with increasing offsets until all
     * available results are retrieved from the Marvel API.
     *
     * @param string $endpoint Marvel API endpoint name (e.g., "characters", "comics").
     * @param int $limit Maximum number of items per request (Marvel max is 100, default 100).
     * @param string|null $modifiedSince Optional filter: only return resources modified since this date (format "YYYY-MM-DD").
     *
     * @return array<int, array> A complete list of results returned by the Marvel API for the given endpoint.
     *
     * @throws \RuntimeException            If a non-200 HTTP status code is returned by the Marvel API.
     * @throws TransportExceptionInterface  On network-related errors.
     * @throws ClientExceptionInterface     On client-side errors (4xx).
     * @throws ServerExceptionInterface     On server-side errors (5xx).
     * @throws RedirectionExceptionInterface If an invalid redirect is encountered.
     * @throws DecodingExceptionInterface   If the response JSON cannot be decoded.
     *
     * @example
     * // Fetch all Marvel characters without worrying about pagination
     * $allCharacters = $this->fetchAll('characters');
     *
     * @example
     * // Fetch all Marvel series modified since January 1st, 2024
     * $recentSeries = $this->fetchAll('series', 100, '2024-01-01');
     */

    private function fetchAll(string $endpoint, int $limit = 100, ?string $modifiedSince = null): array
    {
        $allResults = [];
        $offset = 0;

        do {
            $batch = $this->fetchData($endpoint, $limit, $offset, $modifiedSince);
            $allResults = array_merge($allResults, $batch);
            $offset += $limit;
        } while (count($batch) === $limit);

        return $allResults;
    }

    /**
     * Extract marvelId from resourceURI.
     *
     * @param string $uri The Marvel resource URI.
     * @return int||null The extracted Marvel ID, or null if not found.
     */
    private function catchIdWithURI(string $uri): ?int
    {
        if (!empty($uri)) {
            preg_match('/\/(\d+)$/', $uri, $matches);
            return (int)$matches[1] ?? null;
        }
        return null;
    }

    /**
     * Retrieves a list of Marvel characters.
     *
     * @param int|null $limit Maximum number of items per request (Marvel max is 100, default 100).
     * @param string|null $modifiedSince Last updated date (optional).
     *
     * @return array<int, array{marvelId:int, name:string, description:string, thumbnail:string}>
     *
     * @throws TransportExceptionInterface|ClientExceptionInterface|ServerExceptionInterface|
     *         RedirectionExceptionInterface|DecodingExceptionInterface|\RuntimeException
     */
    public function getCharacters(int $limit = null, ?string $modifiedSince = null): array
    {
        $results = $this->fetchAll('characters', $limit, $modifiedSince);
        return array_map(fn($c) => [
            'marvelId' => $c['id'],
            'name' => $c['name'],
            'description' => $c['description'] ?: 'No description available',
            'thumbnail' => "{$c['thumbnail']['path']}.{$c['thumbnail']['extension']}",
        ], $results);
    }

    /**
     * Retrieves a list of Marvel comics.
     *
     * @param int|null $limit Maximum number of items per request (Marvel max is 100, default 100).
     * @param string|null $modifiedSince Last updated date (optional).
     *
     * @return array<int, array{
     *     marvelId:int,
     *     title:string,
     *     description:string,
     *     thumbnail:string,
     *     pageCount:int,
     *     dates:string|null,
     *     variants:array{int|null},
     *     creators:array<int,array{marvelCreatorId:int|null, role:string}>,
     *     marvelIdSerie:int,
     *     marvelIdsCharacter: array{int|null}
     * }>
     *
     * @throws TransportExceptionInterface|ClientExceptionInterface|ServerExceptionInterface|
     *         RedirectionExceptionInterface|DecodingExceptionInterface|\RuntimeException
     */
    public function getComics(int $limit = null, ?string $modifiedSince = null): array
    {
        $results = $this->fetchAll('comics', $limit, $modifiedSince);

        return array_map(function ($c) {

            return [
                'marvelId' => $c['id'],
                'title' => $c['title'],
                'description' => $c['description'] ?: 'No description available',
                'thumbnail' => "{$c['thumbnail']['path']}.{$c['thumbnail']['extension']}",
                'pageCount' => $c['pageCount'],

                'dates' => (function ($dates) {
                    $onsale = array_filter($dates, fn($d) => $d['type'] === 'onsaleDate');
                    return !empty($onsale) ? explode('T', array_values($onsale)[0]['date'])[0] : null;
                })($c['dates']),

                'variants' => array_map(function ($variant) {
                    $marvelVariantId = $this->catchIdWithURI($variant['resourceURI']);
                    return (int)$marvelVariantId;
                }, $c['variants'] ?? []),

                'creators' => array_map(function ($creator) {
                    $marvelCreatorId = $this->catchIdWithURI($creator['resourceURI']);
                    return [
                        'marvelCreatorId' => $marvelCreatorId,
                        'role' => $creator['role'],
                    ];
                }, $c['creators']['items'] ?? []),

                'marvelIdSerie' => $this->catchIdWithURI($c['series']['resourceURI']),
                'marvelIdsCharacter' => array_map(function ($character) {
                    return $this->catchIdWithURI($character['resourceURI']);

                }, $c['characters']['items'] ?? [])
            ];
        }, $results);
    }

    /**
     * Retrieves a list of Marvel creators.
     *
     * @param int|null $limit Maximum number of items per request (Marvel max is 100, default 100).
     * @param string|null $modifiedSince Last updated date (optional).
     *
     * @return array<int, array{
     *     marvelId:int,
     *     fullName:string,
     *     firstName:string|null,
     *     lastName:string|null,
     *     thumbnail:string
     * }>
     *
     * @throws TransportExceptionInterface|ClientExceptionInterface|ServerExceptionInterface|
     *         RedirectionExceptionInterface|DecodingExceptionInterface|\RuntimeException
     */
    public function getCreators(int $limit = null, ?string $modifiedSince = null): array
    {
        $results = $this->fetchAll('creators', $limit, $modifiedSince);

        $datas = [];
        foreach ($results as $c) {
            if (empty($c['fullName'])) continue;

            $datas[] = [
                'marvelId' => $c['id'],
                'fullName' => $c['fullName'],
                'firstName' => $c['firstName'] ?? null,
                'lastName' => $c['lastName'] ?? null,
                'thumbnail' => "{$c['thumbnail']['path']}.{$c['thumbnail']['extension']}",
            ];
        }

        return $datas;
    }

    /**
     * Retrieves a list of Marvel series.
     *
     * @param int|null $limit Maximum number of items per request (Marvel max is 100, default 100).
     * @param string|null $modifiedSince Last updated date (optional).
     *
     * @return array<int, array{
     *     marvelId:int,
     *     title:string,
     *     description:string,
     *     thumbnail:string,
     *     startYear:int,
     *     endYear:int,
     *     creators:array<int,array{marvelCreatorId:int|null, role:string}>,
     *     marvelIdSerie:int,
     *     marvelIdsCharacter: array{int|null}
     * }>
     *
     * @throws TransportExceptionInterface|ClientExceptionInterface|ServerExceptionInterface|
     *         RedirectionExceptionInterface|DecodingExceptionInterface|\RuntimeException
     */
    public function getSeries(int $limit = null, ?string $modifiedSince = null): array
    {
        $results = $this->fetchAll('series', $limit, $modifiedSince);

        return array_map(fn($c) => [
            'marvelId' => $c['id'],
            'title' => $c['title'],
            'description' => $c['description'] ?: 'No description available',
            'thumbnail' => "{$c['thumbnail']['path']}.{$c['thumbnail']['extension']}",
            'startYear' => $c['startYear'],
            'endYear' => $c['endYear'],

            'creators' => array_map(function ($creator) {
                $marvelCreatorId = $this->catchIdWithURI($creator['resourceURI']);
                return [
                    'marvelCreatorId' => $marvelCreatorId,
                    'role' => $creator['role'],
                ];
            }, $c['creators']['items'] ?? []),

            'marvelIdsCharacter' => array_map(function ($character) {
                return $this->catchIdWithURI($character['resourceURI']);
            }, $c['characters']['items'] ?? [])
        ], $results);
    }
}
