<?php

namespace App\DataProvider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Repository\SerieRepository;

class SerieDataProvider implements ProviderInterface
{
    /**
     * SerieDataProvider constructor.
     *
     * @param SerieRepository $serieRepository Repository used to fetch serie data
     */
    public function __construct(private SerieRepository $serieRepository)
    {
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

        // Search series by title
        if ($operation->getName() === 'searchSeriesByTitle') {
            $request = $context['request'] ?? null;
            $title = $request?->query->get('title', '');

            $series = $this->serieRepository->searchSeriesByTitle($title);

            // Natural sort by title
            usort($series, fn($a, $b) => strnatcasecmp($a->title, $b->title));

            return $series;
        }

        // Default: return all series
        return $this->serieRepository->findAll();
    }

}
