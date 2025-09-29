<?php

namespace App\Entity;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\SerieRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: SerieRepository::class)]
#[ORM\Table(name: '`serie`', indexes: [
    new ORM\Index(name: 'idx_marvel_id', columns: ['marvelId'])
])]
#[ApiResource(
    normalizationContext: ['groups' => ['serie:read']],
    operations: [
        new GetCollection(
            uriTemplate: '/series'
        ),
        new Get(
            uriTemplate: '/series/{id}',
            uriVariables: ['id' => 'id']
        ),
    ],
)]
class Serie
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['comic:read', 'serie:read'])]
    private ?int $id = null;

    #[ORM\Column(unique: true)]
    #[Groups(['comic:read', 'serie:read'])]
    private ?int $marvelId = null;

    #[ORM\Column(length: 255)]
    #[Groups(['comic:read', 'serie:read'])]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['comic:read', 'serie:read'])]
    private ?string $description = null;

    #[ORM\Column(length: 255)]
    #[Groups(['comic:read', 'serie:read'])]
    private ?string $startYear = null;

    #[ORM\Column(length: 255)]
    #[Groups(['comic:read', 'serie:read'])]
    private ?string $endYear = null;

    #[ORM\Column(length: 255)]
    #[Groups(['comic:read', 'serie:read'])]
    private ?string $thumbnail = null;

    /**
     * @var Collection<int, Comic>
     */
    #[ORM\OneToMany(targetEntity: Comic::class, mappedBy: 'serie')]
    private Collection $comics;

    /**
     * @var Collection<int, Character>
     */
    #[ORM\ManyToMany(targetEntity: Character::class, inversedBy: 'series')]
    private Collection $characters;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $creators = null;

    #[ORM\Column]
    private array $marvelIdsCharacter = [];

    public function __construct()
    {
        $this->comics = new ArrayCollection();
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

    public function getCreators(): ?array
    {
        return $this->creators;
    }

    public function setCreators(?array $creators): static
    {
        $this->creators = $creators;

        return $this;
    }

    public function getMarvelIdsCharacter(): array
    {
        return $this->marvelIdsCharacter;
    }

    public function setMarvelIdsCharacter(array $marvelIdsCharacter): static
    {
        $this->marvelIdsCharacter = $marvelIdsCharacter;

        return $this;
    }
}
