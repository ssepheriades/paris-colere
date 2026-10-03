<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use App\Repository\ControversyRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: ControversyRepository::class)]
#[ApiResource(normalizationContext: ['groups' => ['controversy:read']])]
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
    #[Groups(['controversy:read'])]
    private Collection $controversyItems;

    /**
     * @var Collection<int, Theme>
     */
    #[ORM\ManyToMany(targetEntity: Theme::class, inversedBy: 'controversies')]
    #[Groups(['controversy:read'])]
    private Collection $theme;

    public function __construct()
    {
        $this->controversyItems = new ArrayCollection();
        $this->theme = new ArrayCollection();
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
}
