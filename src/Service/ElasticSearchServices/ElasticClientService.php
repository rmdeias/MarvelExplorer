<?php

namespace App\Service\ElasticSearchServices;

use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;
use Elastic\Elasticsearch\Exception\AuthenticationException;

/**
 * Class ElasticClientService
 *
 * This service provides a configured instance of the Elasticsearch PHP client.
 * It encapsulates the client creation logic and exposes a single entry point
 * to interact with Elasticsearch throughout the application.
 *
 * The Elasticsearch host is injected via environment variables and used to
 * initialize the client connection.
 *
 * Example usage:
 * ```php
 * $elasticClient = $elasticClientService->getClient();
 * $response = $elasticClient->search([
 *     'index' => 'my_index',
 *     'body'  => [
 *         'query' => [
 *             'match' => ['field' => 'value']
 *         ]
 *     ]
 * ]);
 * ```
 *
 * @package App\Service\ElasticSearchServices
 */
class ElasticClientService
{
    /**
     * @var Client The Elasticsearch client instance.
     */
    private Client $client;

    /**
     * ElasticClientService constructor.
     *
     * Initializes the Elasticsearch client with the given host.
     *
     * @param string $elasticHost The Elasticsearch host URL (e.g., "http://localhost:9200"),
     *                            typically injected from the environment (.env).
     * @throws AuthenticationException
     */
    public function __construct(string $elasticHost)
    {
        $this->client = ClientBuilder::create()
            ->setHosts([$elasticHost])
            ->build();
    }

    /**
     * Returns the initialized Elasticsearch client instance.
     *
     * @return Client The configured Elasticsearch client.
     */
    public function getClient(): Client
    {
        return $this->client;
    }
}
