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
use App\Repository\KeyFigureRepository;
use App\Security\ApiAccess;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: KeyFigureRepository::class)]
#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(),
        new Post(security: ApiAccess::WRITE),
        new Put(security: ApiAccess::WRITE),
        new Patch(security: ApiAccess::WRITE),
        new Delete(security: ApiAccess::WRITE),
    ],
    normalizationContext: ['groups' => ['key_figure:read']],
    denormalizationContext: ['groups' => ['api:write']],
)]
class KeyFigure
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Groups(['controversy:read', 'key_figure:read'])]
    private ?Uuid $id = null;

    #[ORM\Column(length: 32)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 32)]
    #[Groups(['controversy:read', 'key_figure:read', 'api:write'])]
    private ?string $figure = null;

    #[ORM\Column(length: 64)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 64)]
    #[Groups(['controversy:read', 'key_figure:read', 'api:write'])]
    private ?string $label = null;

    #[ORM\Column]
    #[Assert\NotNull]
    #[Groups(['controversy:read', 'key_figure:read', 'api:write'])]
    private ?int $priority = 0;

    #[ORM\ManyToOne(inversedBy: 'keyFigures')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    #[Groups(['key_figure:read', 'api:write'])]
    #[ApiProperty(readableLink: false)]
    private ?Controversy $controversy = null;

    #[ORM\Column]
    #[Assert\NotNull]
    #[Groups(['controversy:read', 'key_figure:read', 'api:write'])]
    private ?bool $isVisible = false;

    #[ORM\Column(length: 64, nullable: true)]
    #[Assert\Length(max: 64)]
    #[Groups(['controversy:read', 'key_figure:read', 'api:write'])]
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

    #[Groups(['controversy:read', 'key_figure:read'])]
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
