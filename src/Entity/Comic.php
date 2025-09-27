<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\DataProvider\ComicDataProvider;
use Symfony\Component\Serializer\Annotation\Groups;
use App\Repository\ComicRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ComicRepository::class)]
#[ORM\Table(name: '`comic`', indexes: [
    new ORM\Index(name: 'idx_marvel_id', columns: ['marvelId'])
])]
#[ApiResource(
    normalizationContext: ['groups' => ['comic:read']],
    denormalizationContext: ['groups' => ['comic:write']],
    operations: [
        new GetCollection(
            name: 'default',
            uriTemplate: '/comics'
        ),
        new GetCollection(
            name: 'searchComicsByTitle',
            uriTemplate: '/searchComicsByTitle',
            provider: ComicDataProvider::class,
            paginationEnabled: false
        ),
        new GetCollection(
            name: 'topRecentComics',
            uriTemplate: '/topRecentComics',
            provider: ComicDataProvider::class
        ),
        new Get(
            uriTemplate: '/comics/{id}',
            uriVariables: ['id' => 'id']
        ),
    ],
)]
#[ApiFilter(SearchFilter::class, properties: ['title' => 'partial'])]
#[ApiFilter(OrderFilter::class, properties: ['date' => 'DESC', 'title' => 'ASC'])]
class Comic
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['comic:read'])]
    private ?int $id = null;

    #[ORM\Column(unique: true)]
    #[Groups(['comic:read'])]
    private ?int $marvelId = null;

    #[ORM\Column(length: 255)]
    #[Groups(['comic:read'])]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['comic:read'])]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['comic:read'])]
    private ?string $pageCount = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['comic:read'])]
    private ?string $modified = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['comic:read'])]
    private ?string $thumbnail = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['comic:read'])]
    private ?\DateTimeInterface $date = null;


    /**
     * @var Collection<int, Character>
     */
    #[ORM\ManyToMany(targetEntity: Character::class, inversedBy: 'comics')]
    #[Groups(['comic:read'])]
    private Collection $characters;

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['comic:read'])]
    private array $variants = [];


    #[ORM\ManyToOne(inversedBy: 'comics')]
    #[Groups(['comic:read'])]
    private ?Serie $serie = null;

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['comic:read'])]
    private ?array $creators = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Groups(['comic:read'])]
    private ?int $marvelIdSerie = null;

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['comic:read'])]
    private array $marvelIdsCharacter = [];

    #[ORM\Column(length: 255)]
    #[Groups(['comic:read'])]
    private string $slug;

    public function __construct()
    {
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

    public function getPageCount(): ?string
    {
        return $this->pageCount;
    }

    public function setPageCount(?string $pageCount): static
    {
        $this->pageCount = $pageCount;

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

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }


    public function setDate(?\DateTimeInterface $date): static
    {
        $this->date = $date;
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

    public function getVariants(): ?array
    {
        return $this->variants;
    }

    public function setVariants(?array $variants): static
    {
        $this->variants = $variants;

        return $this;
    }

    public function addVariant(int $variantId): self
    {
        if (!in_array($variantId, $this->variants ?? [], true)) {
            $this->variants[] = $variantId;
        }
        return $this;
    }


    public function getSerie(): ?Serie
    {
        return $this->serie;
    }

    public function setSerie(?Serie $serie): static
    {
        $this->serie = $serie;

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

    public function getMarvelIdSerie(): ?int
    {
        return $this->marvelIdSerie;
    }

    public function setMarvelIdSerie(int $marvelIdSerie): static
    {
        $this->marvelIdSerie = $marvelIdSerie;

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

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;
        return $this;
    }
}
