<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\PersonRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: PersonRepository::class)]
#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(),
    ],
    normalizationContext: ['groups' => ['person:read']],
)]
class Person
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Groups(['person:read'])]
    private ?Uuid $id = null;

    #[ORM\Column(length: 64)]
    #[Groups(['person:read'])]
    private ?string $firstname = null;

    #[ORM\Column(length: 64)]
    #[Groups(['person:read'])]
    private ?string $lastname = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[ApiProperty(readable: false, writable: false)]
    private ?string $photo = null;

    /**
     * @var Collection<int, Party>
     */
    #[ORM\ManyToMany(targetEntity: Party::class, inversedBy: 'people')]
    #[Groups(['person:read'])]
    private Collection $party;

    /**
     * @var Collection<int, PersonLegalEntity>
     */
    #[ORM\OneToMany(targetEntity: PersonLegalEntity::class, mappedBy: 'person', cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['startDate' => 'DESC'])]
    #[Groups(['person:read'])]
    private Collection $personLegalEntities;

    /**
     * @var Collection<int, ControversyItem>
     */
    #[ORM\ManyToMany(targetEntity: ControversyItem::class, mappedBy: 'people')]
    private Collection $controversyItems;

    #[ORM\Column(length: 128)]
    #[Groups(['person:read'])]
    private ?string $shortDescription = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['person:read'])]
    private ?string $bio = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['person:read'])]
    private ?string $wikiUrl = null;

    #[ORM\Column]
    private ?bool $isVisible = null;

    public function __construct()
    {
        $this->party = new ArrayCollection();
        $this->personLegalEntities = new ArrayCollection();
        $this->controversyItems = new ArrayCollection();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): static
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): static
    {
        $this->lastname = $lastname;

        return $this;
    }

    public function getPhoto(): ?string
    {
        return $this->photo;
    }

    public function setPhoto(?string $photo): static
    {
        $this->photo = $photo;

        return $this;
    }

    #[ApiProperty(writable: false)]
    #[SerializedName('photo')]
    #[Groups(['person:read'])]
    public function getPhotoUrl(): ?string
    {
        return null === $this->photo ? null : '/uploads/photos/'.$this->photo;
    }

    /**
     * @return Collection<int, Party>
     */
    public function getParty(): Collection
    {
        return $this->party;
    }

    public function addParty(Party $party): static
    {
        if (!$this->party->contains($party)) {
            $this->party->add($party);
            $party->addPerson($this);
        }

        return $this;
    }

    public function removeParty(Party $party): static
    {
        if ($this->party->removeElement($party)) {
            $party->removePerson($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, PersonLegalEntity>
     */
    public function getPersonLegalEntities(): Collection
    {
        return $this->personLegalEntities;
    }

    public function addPersonLegalEntity(PersonLegalEntity $personLegalEntity): static
    {
        if (!$this->personLegalEntities->contains($personLegalEntity)) {
            $this->personLegalEntities->add($personLegalEntity);
            $personLegalEntity->setPerson($this);
        }

        return $this;
    }

    public function removePersonLegalEntity(PersonLegalEntity $personLegalEntity): static
    {
        $this->personLegalEntities->removeElement($personLegalEntity);

        return $this;
    }

    /**
     * @return Collection<int, ControversyItem>
     */
    public function getControversyItems(): Collection
    {
        return $this->controversyItems;
    }

    /**
     * @return list<ControversyItem>
     */
    #[Groups(['person:read'])]
    #[SerializedName('controversyItems')]
    public function getVisibleControversyItems(): array
    {
        return $this->controversyItems->filter(
            static fn (ControversyItem $item): bool => true === $item->isVisible(),
        )->getValues();
    }

    public function addControversyItem(ControversyItem $controversyItem): static
    {
        if (!$this->controversyItems->contains($controversyItem)) {
            $this->controversyItems->add($controversyItem);
            $controversyItem->addPerson($this);
        }

        return $this;
    }

    public function removeControversyItem(ControversyItem $controversyItem): static
    {
        if ($this->controversyItems->removeElement($controversyItem)) {
            $controversyItem->removePerson($this);
        }

        return $this;
    }

    public function __toString(): string
    {
        $label = trim(($this->firstname ?? '').' '.($this->lastname ?? ''));

        return '' !== $label ? $label : 'Personne';
    }

    public function getShortDescription(): ?string
    {
        return $this->shortDescription;
    }

    public function setShortDescription(string $shortDescription): static
    {
        $this->shortDescription = $shortDescription;

        return $this;
    }

    public function getBio(): ?string
    {
        return $this->bio;
    }

    public function setBio(?string $bio): static
    {
        $this->bio = $bio;

        return $this;
    }

    public function getWikiUrl(): ?string
    {
        return $this->wikiUrl;
    }

    public function setWikiUrl(?string $wikiUrl): static
    {
        $this->wikiUrl = $wikiUrl;

        return $this;
    }

    public function isVisible(): ?bool
    {
        return $this->isVisible;
    }

    public function setIsVisible(bool $isVisible): static
    {
        $this->isVisible = $isVisible;

        return $this;
    }
}
