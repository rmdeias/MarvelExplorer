<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiProperty;
use App\Repository\CreatorRepository;
use Doctrine\Common\Collections\ArrayCollection;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: CreatorRepository::class)]
#[ORM\Table(name: '`creator`', indexes: [
    new ORM\Index(name: 'idx_marvel_id', columns: ['marvelId'])
])]

#[ApiResource(
    normalizationContext: ['groups' => ['creator:read']],
    denormalizationContext: ['groups' => ['creator:write']],
    operations: [
        new GetCollection(
            uriTemplate: '/creators'
        ),
        new Get(
            uriTemplate: '/creators/{id}',
            uriVariables: ['id' => 'marvelId']
        ),
    ],
)]
class Creator
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(unique: true)]
    #[ApiProperty(identifier: true)]
    #[Groups(['creator:read'])]
    private ?int $marvelId = null;

    #[ORM\Column(length: 255)]
    private ?string $firstName = null;

    #[ORM\Column(length: 255)]
    private ?string $lastName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $modified = null;

    #[ORM\Column(length: 255)]
    #[Groups(['creator:read'])]
    private ?string $thumbnail = null;

    #[ORM\Column(length: 255)]
    #[Groups(['creator:read'])]
    private ?string $fullName = null;


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

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getModified(): ?string
    {
        return $this->modified;
    }

    public function setModified(string $modified): static
    {
        $this->modified = $modified;

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

    public function getFullName(): ?string
    {
        return $this->fullName;
    }

    public function setFullName(string $fullName): static
    {
        $this->fullName = $fullName;

        return $this;
    }

}
