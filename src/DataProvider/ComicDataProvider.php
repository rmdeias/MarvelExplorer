<?php

namespace App\DataProvider;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Repository\ComicRepository;

final class ComicDataProvider implements ProviderInterface
{
    public function __construct(private ComicRepository $comicRepository)
    {
    }

    /**
     * @param array $context
     */
    /**
     * @param Operation $operation
     * @param array $uriVariables
     * @param array $context
     *
     * @return object|array|null
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {

        // Top 10 récents
        if ($operation->getName() === 'topRecentComics') {
            return $this->comicRepository->findTopRecentComics();
        }

        // Recherche par titre
        if ($operation->getName() === 'searchComicsByTitle') {

            $request = $context['request'] ?? null;
            $title = $request?->query->get('title', '');

            $comics = $this->comicRepository->searchComicsByTitle($title);

            // Tri naturel
            usort($comics, function ($a, $b) {
                return strnatcasecmp($a->getTitle(), $b->getTitle());
            });

            return $comics;
        }

        return $this->comicRepository->findAll();
    }
}
