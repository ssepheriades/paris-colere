<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\ControversyRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: ControversyRepository::class)]
#[ApiResource]
class Controversy
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\Column(length: 64)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $shortDescription = null;

    /**
     * @var Collection<int, ControversyItem>
     */
    #[ORM\OneToMany(targetEntity: ControversyItem::class, mappedBy: 'controversy')]
    private Collection $controversyItems;

    public function __construct()
    {
        $this->controversyItems = new ArrayCollection();
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
        if ($this->controversyItems->removeElement($controversyItem)) {
            // set the owning side to null (unless already changed)
            if ($controversyItem->getControversy() === $this) {
                $controversyItem->setControversy(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->name ?? 'Affaire';
    }
}
