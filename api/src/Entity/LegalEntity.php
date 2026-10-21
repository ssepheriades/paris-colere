<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Enum\LegalEntityType;
use App\Repository\LegalEntityRepository;
use App\Security\ApiAccess;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: LegalEntityRepository::class)]
#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(),
        new Post(security: ApiAccess::WRITE),
        new Put(security: ApiAccess::WRITE),
        new Patch(security: ApiAccess::WRITE),
        new Delete(security: ApiAccess::WRITE),
    ],
    denormalizationContext: ['groups' => ['api:write']],
)]
class LegalEntity
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Groups(['person:read'])]
    private ?Uuid $id = null;

    #[ORM\Column(length: 64)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 64)]
    #[Groups(['person:read', 'api:write'])]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    #[Groups(['person:read', 'api:write'])]
    private ?string $shortDescription = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    #[Groups(['api:write'])]
    private ?string $website = null;

    #[ORM\Column(length: 32, enumType: LegalEntityType::class)]
    #[Assert\NotNull]
    #[Groups(['person:read', 'api:write'])]
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
