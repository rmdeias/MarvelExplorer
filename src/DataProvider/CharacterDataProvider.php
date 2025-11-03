<?php

namespace App\DataProvider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\DTO\CharactersListDTO;
use App\Repository\CharacterRepository;
use App\Service\ElasticSearchServices\ElasticSearchService;

final readonly class CharacterDataProvider implements ProviderInterface
{

    public function __construct(
        private CharacterRepository  $characterRepository,
        private ElasticSearchService $elasticSearchService
    )
    {
    }

    /**
     * Maps an array of data to a CharactersListDTO.
     *
     * @param array $source
     * @return CharactersListDTO
     * @throws \Exception
     */
    protected function mapToCharactersDTO(array $source): CharactersListDTO
    {
        return new CharactersListDTO(
            (int)($source['marvelId'] ?? 0),
            $source['name'] ?? '',
            $source['thumbnail'] ?? ''
        );
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $itemsPerPage = $context['filters']['itemsPerPage'] ?? 100;

        // Search characters by name
        if ($operation->getName() === 'searchCharactersByName') {

            $request = $context['request'] ?? null;
            $name = $request?->query->get('name', '');

            $characters = $this->elasticSearchService->search(
                'characters',
                'name',
                $name,
                [],
                100,
                fn(array $source) => $this->mapToCharactersDTO($source)
            );

            usort($characters, fn($a, $b) => strnatcasecmp($a->name, $b->name));
            return
                [
                    'itemsPerPage' => $itemsPerPage,
                    'characters' => $characters,
                ];
        }
        // Default: return all comics
        return $this->characterRepository->findAll();

    }
}
