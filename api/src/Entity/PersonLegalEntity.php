<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Repository\PersonLegalEntityRepository;
use App\Security\ApiAccess;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PersonLegalEntityRepository::class)]
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
class PersonLegalEntity
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Groups(['person:read'])]
    private ?Uuid $id = null;

    #[ORM\ManyToOne(inversedBy: 'personLegalEntities')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    #[Groups(['api:write'])]
    private ?Person $person = null;

    #[ORM\ManyToOne(inversedBy: 'personLegalEntities')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    #[Groups(['person:read', 'api:write'])]
    private ?LegalEntity $legalEntity = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotNull]
    #[Groups(['person:read', 'api:write'])]
    private ?\DateTime $startDate = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    #[Groups(['person:read', 'api:write'])]
    private ?\DateTime $endDate = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    #[Groups(['person:read', 'api:write'])]
    private ?string $position = null;

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getPerson(): ?Person
    {
        return $this->person;
    }

    public function setPerson(Person $person): static
    {
        if ($this->person === $person) {
            return $this;
        }

        $previous = $this->person;
        $this->person = $person;
        $previous?->removePersonLegalEntity($this);
        $person->addPersonLegalEntity($this);

        return $this;
    }

    public function getLegalEntity(): ?LegalEntity
    {
        return $this->legalEntity;
    }

    public function setLegalEntity(LegalEntity $legalEntity): static
    {
        if ($this->legalEntity === $legalEntity) {
            return $this;
        }

        $previous = $this->legalEntity;
        $this->legalEntity = $legalEntity;
        $previous?->removePersonLegalEntity($this);
        $legalEntity->addPersonLegalEntity($this);

        return $this;
    }

    public function getStartDate(): ?\DateTime
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTime $startDate): static
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?\DateTime
    {
        return $this->endDate;
    }

    public function setEndDate(?\DateTime $endDate): static
    {
        $this->endDate = $endDate;

        return $this;
    }

    public function getPosition(): ?string
    {
        return $this->position;
    }

    public function setPosition(?string $position): static
    {
        $this->position = $position;

        return $this;
    }

    public function __toString(): string
    {
        if (null !== $this->position && '' !== $this->position) {
            return $this->position;
        }

        return ($this->person ?? 'Personne').' — '.($this->legalEntity ?? 'Structure');
    }
}
