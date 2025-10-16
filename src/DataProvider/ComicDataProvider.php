<?php

namespace App\DataProvider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\DTO\ComicsListDTO;
use App\Repository\ComicRepository;
use App\Service\ElasticSearchServices\ElasticSearchService;

/**
 * ComicDataProvider
 *
 * Custom data provider for the Comic entity.
 *
 * Handles different operations:
 * - topRecentComics: returns the top 10 most recent comics
 * - comics: returns sort and filtered comics
 * - searchComicsByTitle: searches comics by title using Elasticsearch
 * - default: returns all comics
 */
final readonly class ComicDataProvider implements ProviderInterface
{
    /**
     * @param ComicRepository $comicRepository Repository for fetching comic data
     * @param ElasticSearchService $elasticSearchService Service for searching comics in Elasticsearch
     */
    public function __construct(
        private ComicRepository      $comicRepository,
        private ElasticSearchService $elasticSearchService
    )
    {
    }

    /**
     * Maps an array of data to a ComicsListDTO.
     *
     * @param array $source
     * @return ComicsListDTO
     * @throws \Exception
     */
    protected function mapToComicsDTO(array $source): ComicsListDTO
    {
        return new ComicsListDTO(
            (int)($source['marvelId'] ?? 0),
            $source['title'] ?? '',
            isset($source['date']) ? new \DateTimeImmutable($source['date']) : null,
            $source['thumbnail'] ?? ''
        );
    }

    /**
     * Provides comic data based on the operation name.
     *
     * @param Operation $operation The API Platform operation being executed
     * @param array $uriVariables Variables extracted from the URI
     * @param array $context Context passed by API Platform (e.g., the current request)
     *
     * @return object|array|null Returns an array of comics, a single comic object, or null
     *
     * @throws \Exception Can throw exceptions if repository methods fail
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        if ($operation->getName() === 'topRecentComics') {
            return $this->comicRepository->findTopRecentComics();
        }

        if ($operation->getName() === 'comics') {
            $page = $context['filters']['page'] ?? 1;
            $itemsPerPage = $context['filters']['itemsPerPage'] ?? 100;

            /// Retrieving filtered comics from the repository side with pagination and sort them in alphabetical order
            $comics = $this->comicRepository->findFilteredComics($page, $itemsPerPage);
            usort($comics, fn($a, $b) => strnatcasecmp($a->title, $b->title));

            $totalItems = $this->comicRepository->countFilteredComics()['totalItems'];

            return
                ['totalItems' => $totalItems,
                    'comics' => $comics,
                ];

        }

        if ($operation->getName() === 'searchComicsByTitle') {
            $request = $context['request'] ?? null;
            $title = $request?->query->get('title', '');

            $comics = $this->elasticSearchService->search(
                'comics',
                'title',
                $title,
                ['variant', 'paperback', 'hardcover'],
                500,
                fn(array $source) => $this->mapToComicsDTO($source)
            );

            usort($comics, fn($a, $b) => strnatcasecmp($a->title, $b->title));

            return $comics;
        }

        return $this->comicRepository->findAll();
    }
}
