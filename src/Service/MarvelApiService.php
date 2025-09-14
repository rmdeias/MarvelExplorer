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
 * Service for interacting with the Marvel API.
 * Provides methods to fetch characters and comics.
 */
class MarvelApiService
{
    private HttpClientInterface $client;
    private string $baseUrl = 'https://gateway.marvel.com/v1/public';
    private string $publicKey;
    private string $privateKey;

    /**
     * @param HttpClientInterface $client Symfony HTTP client.
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
     * @return array{ts:int,apikey:string,hash:string} Auth parameters.
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
     * Makes a request to the Marvel API and returns the results.
     *
     * @param string $endpoint API endpoint (e.g., "characters", "comics").
     * @param int $limit Maximum number of results (Marvel max is 100).
     * @param int $offset Offset for pagination.
     *
     * @return array The decoded API results.
     *
     * @throws TransportExceptionInterface    On network issues.
     * @throws ClientExceptionInterface       On client errors (4xx).
     * @throws RedirectionExceptionInterface  On redirection errors.
     * @throws ServerExceptionInterface       On server errors (5xx).
     * @throws DecodingExceptionInterface     On JSON decoding errors.
     * @throws \RuntimeException              If the response status is not 200.
     */
    private function fetchData(string $endpoint, int $limit, int $offset): array
    {
        $params = array_merge([
            'limit' => $limit,
            'offset' => $offset,
        ], $this->generateAuthParams());

        $response = $this->client->request('GET', "{$this->baseUrl}/{$endpoint}", [
            'query' => $params,
        ]);

        if ($response->getStatusCode() !== 200) {
            throw new \RuntimeException("Marvel API error: {$response->getStatusCode()}");
        }

        return $response->toArray()['data']['results'] ?? [];
    }

    /**
     * Fetches a list of Marvel characters.
     *
     * @param int $limit Number of characters to fetch (max 100).
     * @param int $offset Offset for pagination.
     *
     * @return array<int, array{id:int,name:string,description:string,thumbnail:string,comics:string[]}>
     *
     * @throws TransportExceptionInterface|ClientExceptionInterface|ServerExceptionInterface|
     *         RedirectionExceptionInterface|DecodingExceptionInterface|\RuntimeException
     */
    public function getCharacters(int $limit, int $offset): array
    {
        $results = $this->fetchData('characters', $limit, $offset);

        $datas = array_map(fn($c) => [
            'id' => $c['id'],
            'name' => $c['name'],
            'description' => $c['description'] ?: 'No description available',
            'thumbnail' => "{$c['thumbnail']['path']}.{$c['thumbnail']['extension']}",
            'comics' => array_column($c['comics']['items'], 'name'),
        ], $results);

        return $datas;
    }

    /**
     * Fetches a list of Marvel comics.
     *
     * @param int $limit Number of comics to fetch (max 100).
     * @param int $offset Offset for pagination.
     *
     * @return array<int, array{id:int,title:string,description:string,thumbnail:string,pageCount:int,dates:array}>
     *
     * @throws TransportExceptionInterface|ClientExceptionInterface|ServerExceptionInterface|
     *         RedirectionExceptionInterface|DecodingExceptionInterface|\RuntimeException
     */
    public function getComics(int $limit, int $offset): array
    {
        $results = $this->fetchData('comics', $limit, $offset);

        $datas = array_map(fn($c) => [
            'id' => $c['id'],
            'title' => $c['title'],
            'description' => $c['description'] ?: 'No description available',
            'thumbnail' => "{$c['thumbnail']['path']}.{$c['thumbnail']['extension']}",
            'pageCount' => $c['pageCount'],
            'dates' => $c['dates'],
        ], $results);

        return $datas;
    }
}
