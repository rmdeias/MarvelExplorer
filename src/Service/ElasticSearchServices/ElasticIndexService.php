<?php

namespace App\Service\ElasticSearchServices;

use Elastic\Elasticsearch\Client;

/**
 * ElasticIndexService
 *
 * Provides methods to manage Elasticsearch indices for comics.
 *
 * Responsibilities:
 * - Retrieve the Elasticsearch client.
 * - Create the "comics" index with proper mappings if it does not exist.
 */
readonly class ElasticIndexService
{
    /**
     * Constructor.
     *
     * @param ElasticClientService $elasticClientService Service providing the Elasticsearch client.
     */
    public function __construct(private ElasticClientService $elasticClientService)
    {
    }

    /**
     * Returns the Elasticsearch client instance.
     *
     * @return Client
     */
    public function getClient(): Client
    {
        return $this->elasticClientService->getClient();
    }

    /**
     * Creates the "comics" index in Elasticsearch with predefined mappings.
     *
     * If the index already exists, this method does nothing.
     */
    public function createComicsIndex(): void
    {
        $client = $this->getClient();
        $indexName = 'comics';

        $exists = $client->indices()->exists(['index' => $indexName]);
        if ($exists->asBool()) {
            return; // Index already exists
        }

        $client->indices()->create([
            'index' => $indexName,
            'body' => [
                'mappings' => [
                    'properties' => [
                        'marvelId' => ['type' => 'integer'],
                        'title'    => ['type' => 'text', 'fields' => ['keyword' => ['type' => 'keyword']]],
                        'date'     => ['type' => 'date'],
                        'thumbnail'=> ['type' => 'keyword'],
                    ],
                ],
            ],
        ]);
    }

    /**
     * Creates the "series" index in Elasticsearch with predefined mappings.
     *
     * If the index already exists, this method does nothing.
     */
    public function createSeriesIndex(): void
    {
        $client = $this->getClient();
        $indexName = 'series';

        $exists = $client->indices()->exists(['index' => $indexName]);
        if ($exists->asBool()) {
            return; // Index already exists
        }

        $client->indices()->create([
            'index' => $indexName,
            'body' => [
                'mappings' => [
                    'properties' => [
                        'marvelId' => ['type' => 'integer'],
                        'title'    => ['type' => 'text', 'fields' => ['keyword' => ['type' => 'keyword']]],
                        'thumbnail'=> ['type' => 'keyword'],
                    ],
                ],
            ],
        ]);
    }
}
