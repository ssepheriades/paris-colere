<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Enum\ControversyItemType;
use App\Repository\ControversyItemRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: ControversyItemRepository::class)]
#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(),
    ],
    normalizationContext: ['groups' => ['controversy:read']],
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
    #[Groups(['controversy:read'])]
    private ?ControversyItemType $type = null;

    #[ORM\Column(length: 128)]
    #[Groups(['controversy:read'])]
    private ?string $title = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Groups(['controversy:read'])]
    private ?\DateTime $date = null;

    /**
     * @var Collection<int, Source>
     */
    #[ORM\OneToMany(targetEntity: Source::class, mappedBy: 'controversyItem', cascade: ['persist'], orphanRemoval: true)]
    private Collection $sources;

    #[ORM\Column]
    private ?bool $isVisible = null;

    #[ORM\ManyToOne(inversedBy: 'controversyItems')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Controversy $controversy = null;

    /**
     * @var Collection<int, Person>
     */
    #[ORM\ManyToMany(targetEntity: Person::class, inversedBy: 'controversyItems')]
    private Collection $people;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['controversy:read'])]
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
