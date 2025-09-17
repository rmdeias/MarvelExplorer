<?php

namespace App\Entity;

use App\Repository\ComicRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ComicRepository::class)]
class Comic
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

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $pageCount = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $modified = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $thumbnail = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $date = null;

    /**
     * @var Collection<int, Character>
     */
    #[ORM\ManyToMany(targetEntity: Character::class, inversedBy: 'comics')]
    private Collection $characters;

    #[ORM\Column(type: 'json', nullable: true)]
    private array $variants = [];


    /**
     * @var Collection<int, Creator>
     */
    #[ORM\ManyToMany(targetEntity: Creator::class, mappedBy: 'comics')]
    private Collection $creators;

    #[ORM\ManyToOne(inversedBy: 'comics')]
    private ?Serie $serie = null;

    public function __construct()
    {
        $this->characters = new ArrayCollection();
        $this->creators = new ArrayCollection();
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

    public function getDate(): ?string
    {
        return $this->date;
    }

    public function setDate(?string $date): static
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
            $creator->addComic($this);
        }

        return $this;
    }

    public function removeCreator(Creator $creator): static
    {
        if ($this->creators->removeElement($creator)) {
            $creator->removeComic($this);
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
}
