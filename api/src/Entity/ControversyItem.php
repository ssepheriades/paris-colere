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
use App\Enum\ControversyItemType;
use App\Repository\ControversyItemRepository;
use App\Security\ApiAccess;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ControversyItemRepository::class)]
#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(),
        new Post(security: ApiAccess::WRITE),
        new Put(security: ApiAccess::WRITE),
        new Patch(security: ApiAccess::WRITE),
        new Delete(security: ApiAccess::WRITE),
    ],
    normalizationContext: ['groups' => ['controversy:read']],
    denormalizationContext: ['groups' => ['api:write']],
)]
class ControversyItem
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Groups(['controversy:read'])]
    private ?Uuid $id = null;

    #[ORM\Column(length: 32, enumType: ControversyItemType::class)]
    #[Assert\NotNull]
    #[Groups(['controversy:read', 'api:write'])]
    private ?ControversyItemType $type = null;

    #[ORM\Column(length: 128)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 128)]
    #[Groups(['controversy:read', 'api:write'])]
    private ?string $title = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotNull]
    #[Groups(['controversy:read', 'api:write'])]
    private ?\DateTime $date = null;

    /**
     * @var Collection<int, Source>
     */
    #[ORM\OneToMany(targetEntity: Source::class, mappedBy: 'controversyItem', cascade: ['persist'], orphanRemoval: true)]
    private Collection $sources;

    #[ORM\Column]
    #[Assert\NotNull]
    #[Groups(['controversy:read', 'api:write'])]
    private ?bool $isVisible = false;

    #[ORM\ManyToOne(inversedBy: 'controversyItems')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    #[Groups(['api:write'])]
    private ?Controversy $controversy = null;

    /**
     * @var Collection<int, Person>
     */
    #[ORM\ManyToMany(targetEntity: Person::class, inversedBy: 'controversyItems')]
    #[Groups(['api:write'])]
    #[ApiProperty(readable: false)]
    private Collection $people;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['controversy:read', 'api:write'])]
    private ?string $shortDescription = null;

    public function __construct()
    {
        $this->sources = new ArrayCollection();
        $this->people = new ArrayCollection();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getType(): ?ControversyItemType
    {
        return $this->type;
    }

    public function setType(ControversyItemType $type): static
    {
        $this->type = $type;

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

    public function getDate(): ?\DateTime
    {
        return $this->date;
    }

    public function setDate(\DateTime $date): static
    {
        $this->date = $date;

        return $this;
    }

    /**
     * @return Collection<int, Source>
     */
    #[Ignore]
    public function getSources(): Collection
    {
        return $this->sources;
    }

    /**
     * @return list<Source>
     */
    #[ApiProperty(writable: false)]
    #[Groups(['controversy:read'])]
    #[SerializedName('sources')]
    public function getVisibleSources(): array
    {
        return $this->sources->filter(
            static fn (Source $source): bool => true === $source->isVisible(),
        )->getValues();
    }

    public function addSource(Source $source): static
    {
        if (!$this->sources->contains($source)) {
            $this->sources->add($source);
            $source->setControversyItem($this);
        }

        return $this;
    }

    public function removeSource(Source $source): static
    {
        $this->sources->removeElement($source);

        return $this;
    }

    public function isVisible(): ?bool
    {
        return $this->isVisible;
    }

    #[Groups(['controversy:read'])]
    #[SerializedName('isVisible')]
    public function getIsVisible(): ?bool
    {
        return $this->isVisible;
    }

    #[Groups(['api:write'])]
    public function setIsVisible(bool $isVisible): static
    {
        $this->isVisible = $isVisible;

        return $this;
    }

    public function getControversy(): ?Controversy
    {
        return $this->controversy;
    }

    public function setControversy(?Controversy $controversy): static
    {
        $this->controversy = $controversy;

        return $this;
    }

    /**
     * @return Collection<int, Person>
     */
    #[Ignore]
    public function getPeople(): Collection
    {
        return $this->people;
    }

    /**
     * @return list<Person>
     */
    #[ApiProperty(writable: false)]
    #[Groups(['controversy:read'])]
    #[SerializedName('people')]
    public function getVisiblePeople(): array
    {
        $people = $this->people->filter(
            static fn (Person $person): bool => true === $person->isVisible(),
        )->getValues();

        usort($people, static function (Person $left, Person $right): int {
            return [$left->getLastname(), $left->getFirstname()] <=> [$right->getLastname(), $right->getFirstname()];
        });

        return $people;
    }

    public function addPerson(Person $person): static
    {
        if (!$this->people->contains($person)) {
            $this->people->add($person);
            $person->addControversyItem($this);
        }

        return $this;
    }

    public function removePerson(Person $person): static
    {
        if ($this->people->removeElement($person)) {
            $person->removeControversyItem($this);
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->title ?? 'Fait';
    }

    public function getShortDescription(): ?string
    {
        return $this->shortDescription;
    }

    public function setShortDescription(?string $shortDescription): static
    {
        $this->shortDescription = $shortDescription;

        return $this;
    }
}
