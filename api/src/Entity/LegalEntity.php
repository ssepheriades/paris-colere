<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Enum\LegalEntityType;
use App\Repository\LegalEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: LegalEntityRepository::class)]
#[ApiResource(operations: [new Get(), new GetCollection()])]
class LegalEntity
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Groups(['person:read'])]
    private ?Uuid $id = null;

    #[ORM\Column(length: 64)]
    #[Groups(['person:read'])]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    #[Groups(['person:read'])]
    private ?string $shortDescription = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $website = null;

    #[ORM\Column(length: 32, enumType: LegalEntityType::class)]
    #[Groups(['person:read'])]
    private ?LegalEntityType $type = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[ApiProperty(readable: false, writable: false)]
    private ?string $logo = null;

    /**
     * @var Collection<int, PersonLegalEntity>
     */
    #[ORM\OneToMany(targetEntity: PersonLegalEntity::class, mappedBy: 'legalEntity')]
    private Collection $personLegalEntities;

    public function __construct()
    {
        $this->personLegalEntities = new ArrayCollection();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
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

    public function getShortDescription(): ?string
    {
        return $this->shortDescription;
    }

    public function setShortDescription(string $shortDescription): static
    {
        $this->shortDescription = $shortDescription;

        return $this;
    }

    public function getWebsite(): ?string
    {
        return $this->website;
    }

    public function setWebsite(?string $website): static
    {
        $this->website = $website;

        return $this;
    }

    public function getType(): ?LegalEntityType
    {
        return $this->type;
    }

    public function setType(LegalEntityType $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getLogo(): ?string
    {
        return $this->logo;
    }

    public function setLogo(?string $logo): static
    {
        $this->logo = $logo;

        return $this;
    }

    #[ApiProperty(writable: false)]
    #[SerializedName('logo')]
    #[Groups(['person:read'])]
    public function getLogoUrl(): ?string
    {
        return null === $this->logo ? null : '/uploads/logos/'.$this->logo;
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
            $personLegalEntity->setLegalEntity($this);
        }

        return $this;
    }

    public function removePersonLegalEntity(PersonLegalEntity $personLegalEntity): static
    {
        $this->personLegalEntities->removeElement($personLegalEntity);

        return $this;
    }

    public function __toString(): string
    {
        return $this->name ?? 'Structure';
    }
}
