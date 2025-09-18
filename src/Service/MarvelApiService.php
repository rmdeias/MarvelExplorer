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
     * Retrieves a list of Marvel characters.
     *
     * @param int $limit Number of characters to fetch (max 100).
     * @param int $offset Pagination offset.
     * @param string|null $modifiedSince Last updated date (optional).
     *
     * @return array<int, array{marvelId:int, name:string, description:string, thumbnail:string}>
     *
     * @throws TransportExceptionInterface|ClientExceptionInterface|ServerExceptionInterface|
     *         RedirectionExceptionInterface|DecodingExceptionInterface|\RuntimeException
     */
    public function getCharacters(int $limit, int $offset, ?string $modifiedSince = null): array
    {
        $results = $this->fetchData('characters', $limit, $offset, $modifiedSince);

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
     * @param int $limit Number of comics to fetch (max 100).
     * @param int $offset Pagination offset.
     * @param string|null $modifiedSince Last updated date (optional).
     *
     * @return array<int, array{
     *     marvelId:int,
     *     title:string,
     *     description:string,
     *     thumbnail:string,
     *     pageCount:int,
     *     dates:string|null,
     *     variants:array,
     *     creators:array<int,array{marvelCreatorId:string|null, role:string}>,
     *     serie_id:string|null
     * }>
     *
     * @throws TransportExceptionInterface|ClientExceptionInterface|ServerExceptionInterface|
     *         RedirectionExceptionInterface|DecodingExceptionInterface|\RuntimeException
     */
    public function getComics(int $limit, int $offset, ?string $modifiedSince = null): array
    {
        $results = $this->fetchData('comics', $limit, $offset, $modifiedSince);

        return array_map(function ($c) {
            $serieId = null;
            if (!empty($c['series']['resourceURI'])) {
                preg_match('/\/(\d+)$/', $c['series']['resourceURI'], $matches);
                $serieId = $matches[1] ?? null;
            }

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
                'variants' => $c['variants'],
                'creators' => array_map(function ($creator) {
                    preg_match('/\/(\d+)$/', $creator['resourceURI'], $matches);
                    return [
                        'marvelCreatorId' => $matches[1] ?? null,
                        'role' => $creator['role'],
                    ];
                }, $c['creators']['items'] ?? []),
                'serie_id' => $serieId,
            ];
        }, $results);
    }

    /**
     * Retrieves a list of Marvel creators.
     *
     * @param int $limit Number of creators to fetch.
     * @param int $offset Pagination offset.
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
    public function getCreators(int $limit, int $offset, ?string $modifiedSince = null): array
    {
        $results = $this->fetchData('creators', $limit, $offset, $modifiedSince);

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
     * @param int $limit Number of series to fetch.
     * @param int $offset Pagination offset.
     * @param string|null $modifiedSince Last updated date (optional).
     *
     * @return array<int, array{
     *     marvelId:int,
     *     title:string,
     *     description:string,
     *     thumbnail:string,
     *     startYear:int,
     *     endYear:int,
     *     creators:array<int,array{marvelCreatorId:string|null, role:string}>
     * }>
     *
     * @throws TransportExceptionInterface|ClientExceptionInterface|ServerExceptionInterface|
     *         RedirectionExceptionInterface|DecodingExceptionInterface|\RuntimeException
     */
    public function getSeries(int $limit, int $offset, ?string $modifiedSince = null): array
    {
        $results = $this->fetchData('series', $limit, $offset, $modifiedSince);

        return array_map(fn($c) => [
            'marvelId' => $c['id'],
            'title' => $c['title'],
            'description' => $c['description'] ?: 'No description available',
            'thumbnail' => "{$c['thumbnail']['path']}.{$c['thumbnail']['extension']}",
            'startYear' => $c['startYear'],
            'endYear' => $c['endYear'],
            'creators' => array_map(function ($creator) {
                preg_match('/\/(\d+)$/', $creator['resourceURI'], $matches);
                return [
                    'marvelCreatorId' => $matches[1] ?? null,
                    'role' => $creator['role'],
                ];
            }, $c['creators']['items'] ?? []),
        ], $results);
    }
}
