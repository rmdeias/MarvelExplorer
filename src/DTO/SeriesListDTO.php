<?php

namespace App\DTO;

use Symfony\Component\Serializer\Annotation\Groups;


/**
 * Class SerieDTO
 *
 * Data Transfer Object representing a serie with only the necessary fields
 * for API responses or front-end rendering.
 *
 * This DTO contains:
 * - marvelId : the serie's marvelId
 * - title: the serie's title
 * - thumbnail: URL or path to the serie's thumbnail image
 *
 * DTOs are simple objects meant to transfer data without any business logic.
 */
class SeriesListDTO
{

    #[Groups(['serie:read'])]
    public int $marvelId;

    #[Groups(['serie:read'])]
    public string $title;

    #[Groups(['serie:read'])]
    public string $thumbnail;


    public function __construct(int $marvelId, string $title, ?string $thumbnail/*,?string $cover = null*/)
    {
        $this->marvelId = $marvelId;
        $this->title = $title;
        //$this->thumbnail = !empty($thumbnail) ? $thumbnail : ($cover ?? '');
        $this->thumbnail = $thumbnail;
    }
}
