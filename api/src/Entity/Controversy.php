<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\ControversyRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: ControversyRepository::class)]
#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(),
    ],
    normalizationContext: ['groups' => ['controversy:read']],
)]
class Controversy
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Groups(['controversy:read'])]
    private ?Uuid $id = null;

    #[ORM\Column(length: 64)]
    #[Groups(['controversy:read'])]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    #[Groups(['controversy:read'])]
    private ?string $shortDescription = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[ApiProperty(readable: false, writable: false)]
    private ?string $illustration = null;

    /**
     * @var Collection<int, ControversyItem>
     */
    #[ORM\OneToMany(targetEntity: ControversyItem::class, mappedBy: 'controversy', cascade: ['persist'], orphanRemoval: true)]
    private Collection $controversyItems;

    /**
     * @var Collection<int, Theme>
     */
    #[ORM\ManyToMany(targetEntity: Theme::class, inversedBy: 'controversies')]
    #[Groups(['controversy:read'])]
    private Collection $theme;

    #[ORM\Column]
    private ?bool $isVisible = null;

    /**
     * @var Collection<int, KeyFigure>
     */
    #[ORM\OneToMany(targetEntity: KeyFigure::class, mappedBy: 'controversy', cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['priority' => 'ASC'])]
    private Collection $keyFigures;

    public function __construct()
    {
        $this->controversyItems = new ArrayCollection();
        $this->theme = new ArrayCollection();
        $this->keyFigures = new ArrayCollection();
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

    public function getIllustration(): ?string
    {
        return $this->illustration;
    }

    public function setIllustration(?string $illustration): static
    {
        $this->illustration = $illustration;

        return $this;
    }

    #[ApiProperty(writable: false)]
    #[SerializedName('illustration')]
    #[Groups(['controversy:read'])]
    public function getIllustrationUrl(): ?string
    {
        return null === $this->illustration ? null : '/uploads/illustrations/'.$this->illustration;
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
    #[ApiProperty(writable: false)]
    #[Groups(['controversy:read'])]
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
            $controversyItem->setControversy($this);
        }

        return $this;
    }

    public function removeControversyItem(ControversyItem $controversyItem): static
    {
        $this->controversyItems->removeElement($controversyItem);

        return $this;
    }

    public function __toString(): string
    {
        return $this->name ?? 'Affaire';
    }

    /**
     * @return Collection<int, Theme>
     */
    public function getTheme(): Collection
    {
        return $this->theme;
    }

    public function addTheme(Theme $theme): static
    {
        if (!$this->theme->contains($theme)) {
            $this->theme->add($theme);
        }

        return $this;
    }

    public function removeTheme(Theme $theme): static
    {
        $this->theme->removeElement($theme);

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

    /**
     * @return Collection<int, KeyFigure>
     */
    public function getKeyFigures(): Collection
    {
        return $this->keyFigures;
    }

    /**
     * @return list<KeyFigure>
     */
    #[ApiProperty(writable: false)]
    #[Groups(['controversy:read'])]
    #[SerializedName('keyFigures')]
    public function getVisibleKeyFigures(): array
    {
        return $this->keyFigures->filter(
            static fn (KeyFigure $keyFigure): bool => true === $keyFigure->isVisible(),
        )->getValues();
    }

    public function addKeyFigure(KeyFigure $keyFigure): static
    {
        if (!$this->keyFigures->contains($keyFigure)) {
            $this->keyFigures->add($keyFigure);
            $keyFigure->setControversy($this);
        }

        return $this;
    }

    public function removeKeyFigure(KeyFigure $keyFigure): static
    {
        $this->keyFigures->removeElement($keyFigure);

        return $this;
    }
}
