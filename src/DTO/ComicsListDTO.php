<?php

namespace App\DTO;

use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Class ComicDTO
 *
 * Data Transfer Object representing a comic with only the necessary fields
 * for API responses or front-end rendering.
 *
 * This DTO contains:
 * - id : the comic's id from db
 * - title: the comic's title
 * - date: the release or publication date
 * - thumbnail: URL or path to the comic's thumbnail image
 * - slug: the comic's title for url
 *
 * DTOs are simple objects meant to transfer data without any business logic.
 */
class ComicsListDTO
{
    #[Groups(['comic:read'])]
    public int $marvelId;

    #[Groups(['comic:read'])]
    public string $title;

    #[Groups(['comic:read'])]
    public \DateTimeInterface $date;

    #[Groups(['comic:read'])]
    public string $thumbnail;



    public function __construct(int $marvelId, string $title, \DateTimeInterface $date, string $thumbnail)
    {
        $this->marvelId = $marvelId;
        $this->title = $title;
        $this->date = $date;
        $this->thumbnail = $thumbnail;
    }

}
