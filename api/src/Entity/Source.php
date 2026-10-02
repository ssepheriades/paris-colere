<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\SourceRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: SourceRepository::class)]
#[ApiResource]
class Source
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\ManyToOne(inversedBy: 'sources')]
    private ?ControversyItem $controversyItem = null;

    #[ORM\Column(length: 255)]
    private ?string $url = null;

    #[ORM\Column]
    private ?bool $isVisible = null;

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getControversyItem(): ?ControversyItem
    {
        return $this->controversyItem;
    }

    public function setControversyItem(?ControversyItem $controversyItem): static
    {
        $this->controversyItem = $controversyItem;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): static
    {
        $this->url = $url;

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

    public function __toString(): string
    {
        return $this->url ?? 'Source';
    }
}
