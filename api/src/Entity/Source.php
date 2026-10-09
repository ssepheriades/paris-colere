<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Enum\SourceProvider;
use App\Repository\SourceRepository;
use App\Service\ResolvedEmbed;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: SourceRepository::class)]
#[ApiResource(operations: [new Get(), new GetCollection()])]
class Source
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Groups(['controversy:read'])]
    private ?Uuid $id = null;

    #[ORM\ManyToOne(inversedBy: 'sources')]
    private ?ControversyItem $controversyItem = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['controversy:read'])]
    private ?string $url = null;

    #[ORM\Column(length: 32, enumType: SourceProvider::class, options: ['default' => 'link'])]
    #[Groups(['controversy:read'])]
    private SourceProvider $provider = SourceProvider::Link;

    #[ORM\Column(length: 64, nullable: true)]
    #[Groups(['controversy:read'])]
    private ?string $externalId = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['controversy:read'])]
    private ?string $title = null;

    #[ORM\Column(length: 2048, nullable: true)]
    #[Groups(['controversy:read'])]
    private ?string $thumbnailUrl = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['controversy:read'])]
    private ?string $authorName = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['controversy:read'])]
    private ?string $embedText = null;

    #[ORM\Column]
    #[Groups(['controversy:read'])]
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

    public function getProvider(): SourceProvider
    {
        return $this->provider;
    }

    public function setProvider(SourceProvider $provider): static
    {
        $this->provider = $provider;

        return $this;
    }

    public function getExternalId(): ?string
    {
        return $this->externalId;
    }

    public function setExternalId(?string $externalId): static
    {
        $this->externalId = $externalId;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getThumbnailUrl(): ?string
    {
        return $this->thumbnailUrl;
    }

    public function setThumbnailUrl(?string $thumbnailUrl): static
    {
        $this->thumbnailUrl = $thumbnailUrl;

        return $this;
    }

    public function getAuthorName(): ?string
    {
        return $this->authorName;
    }

    public function setAuthorName(?string $authorName): static
    {
        $this->authorName = $authorName;

        return $this;
    }

    public function getEmbedText(): ?string
    {
        return $this->embedText;
    }

    public function setEmbedText(?string $embedText): static
    {
        $this->embedText = $embedText;

        return $this;
    }

    public function applyResolvedEmbed(ResolvedEmbed $embed): void
    {
        $this->provider = $embed->provider;
        $this->externalId = $embed->externalId;
        $this->title = $embed->title;
        $this->thumbnailUrl = $embed->thumbnailUrl;
        $this->authorName = $embed->authorName;
        $this->embedText = $embed->embedText;
    }

    public function isVisible(): ?bool
    {
        return $this->isVisible;
    }

    #[Groups(['controversy:read'])]
    public function getIsVisible(): ?bool
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
