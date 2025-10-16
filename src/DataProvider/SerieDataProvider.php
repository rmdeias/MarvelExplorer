<?php

namespace App\DataProvider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\DTO\SeriesListDTO;
use App\Repository\SerieRepository;
use App\Service\ElasticSearchServices\ElasticSearchService;

/**
 * SerieDataProvider
 *
 * Custom data provider for the Serie entity.
 *
 * Handles different operations:
 * - series: returns sort and filtered series
 * - searchSeriesByTitle: searches series by title using Elasticsearch
 * - default: returns all series
 */
final readonly class SerieDataProvider implements ProviderInterface
{
    /**
     * @param SerieRepository $serieRepository Repository for fetching serie data
     * @param ElasticSearchService $elasticSearchService Service for searching series in Elasticsearch
     */
    public function __construct(
        private SerieRepository      $serieRepository,
        private ElasticSearchService $elasticSearchService
    )
    {
    }

    /**
     * Maps an array of data to a SeriesListDTO.
     *
     * @param array $source
     * @return SeriesListDTO
     * @throws \Exception
     */
    protected function mapToSeriesDTO(array $source): SeriesListDTO
    {
        return new SeriesListDTO(
            (int)($source['marvelId'] ?? 0),
            $source['title'] ?? '',
            $source['thumbnail'] ?? ''
        );
    }

    /**
     * Provides serie data based on the operation name.
     *
     * @param Operation $operation The API Platform operation being executed
     * @param array $uriVariables Variables extracted from the URI
     * @param array $context Context passed by API Platform (e.g., the current request)
     *
     * @return object|array|null Returns an array of series, a single serie object, or null
     *
     * @throws \Exception Can throw exceptions if repository methods fail
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {

        if ($operation->getName() === 'series') {
            $page = $context['filters']['page'] ?? 1;
            $itemsPerPage = $context['filters']['itemsPerPage'] ?? 100;

            /// Retrieving filtered series from the repository side with pagination and sort them in alphabetical order
            $series = $this->serieRepository->findFilteredSeries($page, $itemsPerPage);
            usort($series, fn($a, $b) => strnatcasecmp($a->title, $b->title));

            $totalItems = $this->serieRepository->countFilteredSeries()['totalItems'];

            return
                ['totalItems' => $totalItems,
                    'series' => $series,
                ];

        }

        // Search series by title
        if ($operation->getName() === 'searchSeriesByTitle') {
            $request = $context['request'] ?? null;
            $title = $request?->query->get('title', '');

            $series = $this->elasticSearchService->search(
                'series',
                'title',
                $title,
                ['variant', 'paperback', 'hardcover'],
                500,
                fn(array $source) => $this->mapToSeriesDTO($source)
            );

            usort($series, fn($a, $b) => strnatcasecmp($a->title, $b->title));

            return $series;
        }

        // Default: return all series
        return $this->serieRepository->findAll();
    }

}
