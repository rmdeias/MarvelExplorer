<?php

namespace App\DTO;

use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Class CharacterDTO
 *
 * Data Transfer Object representing a character with only the necessary fields
 * for API responses or front-end rendering.
 *
 * This DTO contains:
 * - marvelId : the character's marvelId
 * - name: the character's name
 * - thumbnail: URL or path to the character's thumbnail image
 *
 * DTOs are simple objects meant to transfer data without any business logic.
 */
class CharactersListDTO
{
    #[Groups(['character:read'])]
    public int $marvelId;

    #[Groups(['character:read'])]
    public string $name;

    #[Groups(['character:read'])]
    public string $thumbnail;

    public function __construct(int $marvelId, string $name, string $thumbnail)
    {
        $this->marvelId = $marvelId;
        $this->name = $name;
        $this->thumbnail = $thumbnail;
    }

}
