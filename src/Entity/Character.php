<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\CharacterRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: CharacterRepository::class)]
#[ORM\Table(name: '`character`', indexes: [
    new ORM\Index(name: 'idx_marvel_id', columns: ['marvelId'])
])]
#[ApiResource(
    normalizationContext: ['groups' => ['character:read']],
    operations: [
        new GetCollection(
            uriTemplate: '/characters'
        ),
        new Get(
            uriTemplate: '/characters/{id}',
            uriVariables: ['id' => 'marvelId']
        ),
    ],
)]
class Character
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['comic:read', 'character:read'])]
    private ?int $id = null;

    #[ORM\Column(unique: true)]
    #[Groups(['comic:read', 'character:read'])]
    private ?int $marvelId = null;

    #[ORM\Column(length: 255)]
    #[Groups(['comic:read', 'character:read'])]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['comic:read', 'character:read'])]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['comic:read', 'character:read'])]
    private ?string $modified = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['comic:read', 'character:read'])]
    private ?string $thumbnail = null;

    /**
     * @var Collection<int, Comic>
     */
    #[ORM\ManyToMany(targetEntity: Comic::class, mappedBy: 'characters')]
    #[Groups(['character:read'])]
    private Collection $comics;

    /**
     * @var Collection<int, Serie>
     */
    #[ORM\ManyToMany(targetEntity: Serie::class, mappedBy: 'characters')]
    private Collection $series;

    public function __construct()
    {
        $this->comics = new ArrayCollection();
        $this->series = new ArrayCollection();
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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

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

    public function getModified(): ?string
    {
        return $this->modified;
    }

    public function setModified(?string $modified): static
    {
        $this->modified = $modified;

        return $this;
    }

    public function getThumbnail(): ?string
    {
        return $this->thumbnail;
    }

    public function setThumbnail(?string $thumbnail): static
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
            $comic->addCharacter($this);
        }

        return $this;
    }

    public function removeComic(Comic $comic): static
    {
        if ($this->comics->removeElement($comic)) {
            $comic->removeCharacter($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Serie>
     */
    public function getSeries(): Collection
    {
        return $this->series;
    }

    public function addSeries(Serie $series): static
    {
        if (!$this->series->contains($series)) {
            $this->series->add($series);
            $series->addCharacter($this);
        }

        return $this;
    }

    public function removeSeries(Serie $series): static
    {
        if ($this->series->removeElement($series)) {
            $series->removeCharacter($this);
        }

        return $this;
    }
}
