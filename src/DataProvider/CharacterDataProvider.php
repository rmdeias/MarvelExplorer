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
        $itemsPerPage = $operation->getPaginationItemsPerPage();
        $request = $context['request'] ?? null;


        if ($operation->getName() === 'characters') {
            $page = $request?->query->get('page');
            /// Retrieving characters from the repository side with paging and sort them in alphabetical order
            $characters = $this->characterRepository->findDTOCharacters((int)$page, $itemsPerPage);
            $totalItems = $this->characterRepository->countCharacters()['totalItems'];
            usort($characters, fn($a, $b) => strnatcasecmp($a->name, $b->name));

            return
                [
                    'totalItems' => $totalItems,
                    'itemsPerPage' => $itemsPerPage,
                    'characters' => $characters,
                ];

        }

        // Search characters by name
        if ($operation->getName() === 'searchCharactersByName') {


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
        // Default: return all characters
        return $this->characterRepository->findAll();

    }
}
