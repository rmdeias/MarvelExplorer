<?php

namespace App\DataProvider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Repository\CharacterRepository;

final readonly class CharacterDataProvider implements ProviderInterface
{

    public function __construct(private CharacterRepository $characterRepository)
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        // Search characters by name
        if ($operation->getName() === 'searchCharactersByName') {
            $request = $context['request'] ?? null;
            $name = $request?->query->get('name', '');

            $characters = $this->characterRepository->searchCharactersByName($name);

            // Natural sort by title
            //usort($characters, fn($a, $b) => strnatcasecmp($a->name, $b->name));
            return $characters;
        }
        // Default: return all comics
        return $this->characterRepository->findAll();
    }
}
