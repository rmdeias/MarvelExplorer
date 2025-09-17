<?php

namespace App\Entity;

use App\Repository\SerieRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SerieRepository::class)]
class Serie
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $marvelId = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 255)]
    private ?string $startYear = null;

    #[ORM\Column(length: 255)]
    private ?string $endYear = null;

    #[ORM\Column(length: 255)]
    private ?string $thumbnail = null;

    /**
     * @var Collection<int, Comic>
     */
    #[ORM\OneToMany(targetEntity: Comic::class, mappedBy: 'serie')]
    private Collection $comics;

    /**
     * @var Collection<int, Creator>
     */
    #[ORM\ManyToMany(targetEntity: Creator::class, inversedBy: 'series')]
    private Collection $creators;

    /**
     * @var Collection<int, Character>
     */
    #[ORM\ManyToMany(targetEntity: Character::class, inversedBy: 'series')]
    private Collection $characters;

    public function __construct()
    {
        $this->comics = new ArrayCollection();
        $this->creators = new ArrayCollection();
        $this->characters = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMarvelId(): ?int
    {
        return $this->marvelId;
    }

    public function setMarvelId(int $marvelId): static
    {
        $this->marvelId = $marvelId;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getStartYear(): ?string
    {
        return $this->startYear;
    }

    public function setStartYear(string $startYear): static
    {
        $this->startYear = $startYear;

        return $this;
    }

    public function getEndYear(): ?string
    {
        return $this->endYear;
    }

    public function setEndYear(string $endYear): static
    {
        $this->endYear = $endYear;

        return $this;
    }

    public function getThumbnail(): ?string
    {
        return $this->thumbnail;
    }

    public function setThumbnail(string $thumbnail): static
    {
        $this->thumbnail = $thumbnail;

        return $this;
    }

    /**
     * @return Collection<int, Comic>
     */
    public function getComics(): Collection
    {
        return $this->comics;
    }

    public function addComic(Comic $comic): static
    {
        if (!$this->comics->contains($comic)) {
            $this->comics->add($comic);
            $comic->setSerie($this);
        }

        return $this;
    }

    public function removeComic(Comic $comic): static
    {
        if ($this->comics->removeElement($comic)) {
            // set the owning side to null (unless already changed)
            if ($comic->getSerie() === $this) {
                $comic->setSerie(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Creator>
     */
    public function getCreators(): Collection
    {
        return $this->creators;
    }

    public function addCreator(Creator $creator): static
    {
        if (!$this->creators->contains($creator)) {
            $this->creators->add($creator);
        }

        return $this;
    }

    public function removeCreator(Creator $creator): static
    {
        $this->creators->removeElement($creator);

        return $this;
    }

    /**
     * @return Collection<int, Character>
     */
    public function getCharacters(): Collection
    {
        return $this->characters;
    }

    public function addCharacter(Character $character): static
    {
        if (!$this->characters->contains($character)) {
            $this->characters->add($character);
        }

        return $this;
    }

    public function removeCharacter(Character $character): static
    {
        $this->characters->removeElement($character);

        return $this;
    }
}
