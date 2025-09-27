<?php

namespace App\Service;

use Symfony\Component\String\Slugger\AsciiSlugger;
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
     * Executes a GET request to the Marvel API and returns decoded results.
     *
     * @param string $endpoint The API endpoint (e.g. "characters", "comics").
     * @param int $limit Maximum number of results (Marvel max: 100).
     * @param int $offset Offset for pagination.
     * @param string|null $modifiedSince Optional: fetch only items modified since this date.
     *
     * @return array The decoded API results.
     *
     * @throws TransportExceptionInterface   On network errors.
     * @throws ClientExceptionInterface      On client (4xx) errors.
     * @throws RedirectionExceptionInterface On redirection errors.
     * @throws ServerExceptionInterface      On server (5xx) errors.
     * @throws DecodingExceptionInterface    On JSON decoding errors.
     * @throws \RuntimeException             If the HTTP status code is not 200.
     */
    private function fetchData(string $endpoint, int $limit, int $offset, ?string $modifiedSince = null): array
    {
        $params = array_merge([
            'limit' => $limit,
            'offset' => $offset,
        ], $this->generateAuthParams());

        // Add param only if not null
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
     * Extracts the Marvel ID from a resource URI.
     *
     * @param string $uri The Marvel resource URI.
     *
     * @return int|null The extracted Marvel ID, or null if none found.
     */
    private function catchIdWithURI(string $uri): ?int
    {
        if (!empty($uri)) {
            preg_match('/\/(\d+)$/', $uri, $matches);
            return (int)$matches[1] ?? null;
        }
        return null;
    }


    private function extractOnsaleDate(array $dates): ?\DateTimeImmutable
    {
        foreach ($dates as $d) {
            if ($d['type'] === 'onsaleDate' && !empty($d['date'])) {
                try {
                    $date = new \DateTimeImmutable($d['date']);
                    // on vérifie que la date est raisonnable
                    if ((int)$date->format('Y') > 1900) {
                        return $date;
                    }
                } catch (\Exception) {
                    return null; // si la date est complètement pourrie
                }
            }
        }
        return null;
    }


    /**
     * Retrieves Marvel characters.
     *
     * @param int $limit Maximum items per request (Marvel max: 100, default: 100).
     * @param int $offset Offset for pagination.
     * @param string|null $modifiedSince Optional: fetch only items modified since this date.
     *
     * @return array<int, array{
     *     marvelId:int,
     *     name:string,
     *     description:string,
     *     thumbnail:string
     * }>
     *
     * @throws TransportExceptionInterface|ClientExceptionInterface|ServerExceptionInterface
     *         |RedirectionExceptionInterface|DecodingExceptionInterface|\RuntimeException
     */
    public function getCharacters(int $limit = 100, int $offset = 0, ?string $modifiedSince = null): array
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
     * Retrieves Marvel comics.
     *
     * @param int $limit Maximum items per request (Marvel max: 100, default: 100).
     * @param int $offset Offset for pagination.
     * @param string|null $modifiedSince Optional: fetch only items modified since this date.
     *
     * @return array<int, array{
     *     marvelId:int,
     *     title:string,
     *     description:string,
     *     thumbnail:string,
     *     pageCount:int,
     *     dates:\DateTimeImmutable|null,
     *     variants:array<int|null>,
     *     creators:array<int, array{marvelCreatorId:int|null, role:string}>,
     *     marvelIdSerie:int,
     *     marvelIdsCharacter:array<int|null>,
     *     slug:string
     * }>
     *
     * @throws TransportExceptionInterface|ClientExceptionInterface|ServerExceptionInterface
     *         |RedirectionExceptionInterface|DecodingExceptionInterface|\RuntimeException
     */
    public function getComics(int $limit = 100, int $offset = 0, ?string $modifiedSince = null): array
    {
        $results = $this->fetchData('comics', $limit, $offset, $modifiedSince);


        return array_map(function ($c) {
            $slugger = new AsciiSlugger();
            if(strpos($c['thumbnail']['path'], 'image_not_available')) {
                $c['thumbnail']['path'] = '';
                $c['thumbnail']['extension'] = '';
            }
            return [
                'marvelId' => $c['id'] ?? null,
                'title' => $c['title'] ?? 'Untitled',
                'description' => $c['description'] ?: 'No description available',
                'thumbnail' => "{$c['thumbnail']['path']}.{$c['thumbnail']['extension']}",
                'pageCount' => $c['pageCount'] ?? 0,
                'dates' => $this->extractOnsaleDate($c['dates'] ?? []),

                'variants' => array_map(
                    fn($variant) => (int)$this->catchIdWithURI($variant['resourceURI']),
                    $c['variants'] ?? []
                ),
                'creators' => array_map(
                    fn($creator) => [
                        'marvelCreatorId' => $this->catchIdWithURI($creator['resourceURI']),
                        'role' => $creator['role'],
                    ],
                    $c['creators']['items'] ?? []
                ),
                'marvelIdSerie' => isset($c['series']['resourceURI'])
                    ? $this->catchIdWithURI($c['series']['resourceURI'])
                    : 0,
                'marvelIdsCharacter' => array_map(
                    fn($character) => $this->catchIdWithURI($character['resourceURI']),
                    $c['characters']['items'] ?? []
                ),
                'slug' => isset($c['title']) ? strtolower($slugger->slug($c['title'])) : 'untitled' ,
            ];
        }, $results);
    }


    /**
     * Retrieves Marvel creators.
     *
     * @param int $limit Maximum items per request (Marvel max: 100, default: 100).
     * @param int $offset Offset for pagination.
     * @param string|null $modifiedSince Optional: fetch only items modified since this date.
     *
     * @return array<int, array{
     *     marvelId:int,
     *     fullName:string,
     *     firstName:string|null,
     *     lastName:string|null,
     *     thumbnail:string
     * }>
     *
     * @throws TransportExceptionInterface|ClientExceptionInterface|ServerExceptionInterface
     *         |RedirectionExceptionInterface|DecodingExceptionInterface|\RuntimeException
     */
    public function getCreators(int $limit = 100, int $offset = 0, ?string $modifiedSince = null): array
    {
        $results = $this->fetchData('creators', $limit, $offset, $modifiedSince);

        $datas = [];
        foreach ($results as $c) {
            if (empty($c['fullName']) && empty($c['firstName']) && empty($c['lastName'])) {
                continue;
            }
            $datas[] = [
                'marvelId' => $c['id'],
                'fullName' => $c['fullName'],
                'firstName' => $c['firstName'],
                'lastName' => $c['lastName'],
                'thumbnail' => "{$c['thumbnail']['path']}.{$c['thumbnail']['extension']}",
            ];
        }

        return $datas;

    }

    /**
     * Retrieves Marvel series.
     *
     * @param int $limit Maximum items per request (Marvel max: 100, default: 100).
     * @param int $offset Offset for pagination.
     * @param string|null $modifiedSince Optional: fetch only items modified since this date.
     *
     * @return array<int, array{
     *     marvelId:int,
     *     title:string,
     *     description:string,
     *     thumbnail:string,
     *     startYear:int,
     *     endYear:int,
     *     creators:array<int, array{marvelCreatorId:int|null, role:string}>,
     *     marvelIdsCharacter:array<int|null>
     * }>
     *
     * @throws TransportExceptionInterface|ClientExceptionInterface|ServerExceptionInterface
     *         |RedirectionExceptionInterface|DecodingExceptionInterface|\RuntimeException
     */
    public function getSeries(int $limit = 100, int $offset = 0, ?string $modifiedSince = null): array
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
