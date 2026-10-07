<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\KeyFigureRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: KeyFigureRepository::class)]
#[ApiResource]
class KeyFigure
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Groups(['controversy:read'])]
    private ?Uuid $id = null;

    #[ORM\Column(length: 32)]
    #[Groups(['controversy:read'])]
    private ?string $figure = null;

    #[ORM\Column(length: 64)]
    #[Groups(['controversy:read'])]
    private ?string $label = null;

    #[ORM\Column]
    #[Groups(['controversy:read'])]
    private ?int $priority = null;

    #[ORM\ManyToOne(inversedBy: 'keyFigures')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Controversy $controversy = null;

    #[ORM\Column]
    #[Groups(['controversy:read'])]
    private ?bool $isVisible = null;

    #[ORM\Column(length: 64, nullable: true)]
    #[Groups(['controversy:read'])]
    private ?string $subLabel = null;

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getFigure(): ?string
    {
        return $this->figure;
    }

    public function setFigure(string $figure): static
    {
        $this->figure = $figure;

        return $this;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getPriority(): ?int
    {
        return $this->priority;
    }

    public function setPriority(int $priority): static
    {
        $this->priority = $priority;

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

    public function isVisible(): ?bool
    {
        return $this->isVisible;
    }

    public function setIsVisible(bool $isVisible): static
    {
        $this->isVisible = $isVisible;

        return $this;
    }

    public function getSubLabel(): ?string
    {
        return $this->subLabel;
    }

    public function setSubLabel(?string $subLabel): static
    {
        $this->subLabel = $subLabel;

        return $this;
    }
}
