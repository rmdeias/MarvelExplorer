<?php

namespace App\DataProvider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Repository\ComicRepository;

/**
 * Class ComicDataProvider
 *
 * Custom data provider for the Comic entity.
 *
 * This provider handles different operations:
 * - "topRecentComics": returns the top 10 most recent comics
 * - "searchComicsByTitle": searches comics by title and applies natural sorting
 * - Default: returns all comics
 *
 * It delegates database queries to the ComicRepository.
 */
final readonly class ComicDataProvider implements ProviderInterface
{
    /**
     * ComicDataProvider constructor.
     *
     * @param ComicRepository $comicRepository Repository used to fetch comic data
     */
    public function __construct(private ComicRepository $comicRepository)
    {
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
        // Top recent comics
        if ($operation->getName() === 'topRecentComics') {
            return $this->comicRepository->findTopRecentComics();
        }

        // Search comics by title
        if ($operation->getName() === 'searchComicsByTitle') {
            $request = $context['request'] ?? null;
            $title = $request?->query->get('title', '');

            $comics = $this->comicRepository->searchComicsByTitle($title);

            // Natural sort by title
            usort($comics, fn($a, $b) => strnatcasecmp($a->title, $b->title));

            return $comics;
        }

        // Default: return all comics
        return $this->comicRepository->findAll();
    }
}
