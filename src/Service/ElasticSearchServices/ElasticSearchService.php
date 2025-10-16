<?php

declare(strict_types=1);

namespace App\Service\ElasticSearchServices;

use Elastic\Elasticsearch\Client;

/**
 * Class ElasticSearchService
 *
 * Provides a high-level abstraction for executing flexible search queries
 * against Elasticsearch indices.
 *
 * This service wraps the Elasticsearch client and offers a simplified API
 * for fuzzy matching, exclusion filters, pagination, and custom mapping of
 * search results to DTOs or arrays.
 *
 * Example usage:
 * ```php
 * $results = $elasticSearchService->search(
 *     index: 'comics',
 *     field: 'title',
 *     query: 'spider',
 *     exclude: ['spoof'],
 *     limit: 10,
 *     dtoMapper: fn(array $source) => new ComicDTO($source)
 * );
 * ```
 *
 * @package App\Service\ElasticSearchServices
 */
final readonly class ElasticSearchService
{
    /**
     * @var Client The Elasticsearch client instance used to execute queries.
     */
    private Client $client;

    /**
     * ElasticSearchService constructor.
     *
     * Initializes the service by retrieving a configured Elasticsearch client
     * from {@see ElasticClientService}.
     *
     * @param ElasticClientService $elasticClientService Service providing a configured Elasticsearch client.
     */
    public function __construct(ElasticClientService $elasticClientService)
    {
        $this->client = $elasticClientService->getClient();
    }

    /**
     * Performs a flexible, fuzzy search on a given Elasticsearch index and field.
     *
     * This method supports:
     * - Fuzzy matching with "AUTO" fuzziness for partial matches.
     * - Excluding results containing specific words.
     * - Pagination via offset and limit.
     * - Custom mapping of results through a callback function (DTO mapper).
     *
     * @param string   $index     The target index name (e.g., "comics", "series", "characters").
     * @param string   $field     The field name to search within (e.g., "title", "name").
     * @param string   $query     The search term or phrase to match.
     * @param array    $exclude   Optional list of words to exclude from results.
     * @param int      $limit     Maximum number of results to return (default: 20).
     * @param callable $dtoMapper Callback that maps each `_source` document to a DTO or array.
     * @param int      $offset    Pagination offset (default: 0).
     *
     * @return array The mapped search results.
     */
    public function search(
        string $index,
        string $field,
        string $query,
        array $exclude = [],
        int $limit = 20,
        callable $dtoMapper,
        int $offset = 0
    ): array {
        if ($query === '') {
            return [];
        }

        // Prepare exclusion filters
        $mustNot = array_map(
            fn(string $word): array => ['match' => [$field => $word]],
            $exclude
        );

        $body = [
            'from' => $offset,
            'size' => $limit,
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'match' => [
                                $field => [
                                    'query' => strtolower($query),
                                    'fuzziness' => 'AUTO',
                                    'operator' => 'and',
                                ],
                            ],
                        ],
                    ],
                    'must_not' => $mustNot,
                ],
            ],
            'sort' => [
                [$field . '.keyword' => ['order' => 'asc']],
            ],
        ];

        $results = $this->client->search([
            'index' => $index,
            'body' => $body,
        ]);

        return array_map(
            static fn(array $hit): mixed => $dtoMapper($hit['_source']),
            $results['hits']['hits']
        );
    }
}
