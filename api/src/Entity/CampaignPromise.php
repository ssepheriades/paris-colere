<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Enum\CampaignPromiseStatus;
use App\Repository\CampaignPromiseRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: CampaignPromiseRepository::class)]
#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(),
    ],
    normalizationContext: ['groups' => ['promise:read']],
)]
class CampaignPromise
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Groups(['promise:read'])]
    private ?Uuid $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['promise:read'])]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['promise:read'])]
    private ?string $quote = null;

    #[ORM\Column(length: 16, enumType: CampaignPromiseStatus::class)]
    #[Groups(['promise:read'])]
    private ?CampaignPromiseStatus $status = null;

    #[ORM\Column(length: 16, nullable: true)]
    private ?string $resultType = null;

    #[ORM\Column]
    private ?bool $isVisible = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[ApiProperty(readable: false, writable: false)]
    private ?string $illustration = null;

    /**
     * @var Collection<int, Theme>
     */
    #[ORM\ManyToMany(targetEntity: Theme::class, inversedBy: 'campaignPromises')]
    #[Groups(['promise:read'])]
    private Collection $theme;

    /**
     * @var Collection<int, Source>
     */
    #[ORM\OneToMany(targetEntity: Source::class, mappedBy: 'campaignPromise', cascade: ['persist'], orphanRemoval: true)]
    private Collection $sources;

    public function __construct()
    {
        $this->theme = new ArrayCollection();
        $this->sources = new ArrayCollection();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
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

    public function getQuote(): ?string
    {
        return $this->quote;
    }

    public function setQuote(string $quote): static
    {
        $this->quote = $quote;

        return $this;
    }

    public function getStatus(): ?CampaignPromiseStatus
    {
        return $this->status;
    }

    public function setStatus(CampaignPromiseStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getResultType(): ?string
    {
        return $this->resultType;
    }

    public function setResultType(?string $resultType): static
    {
        $this->resultType = $resultType;

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

    public function getIllustration(): ?string
    {
        return $this->illustration;
    }

    public function setIllustration(?string $illustration): static
    {
        $illustration = null === $illustration ? null : trim($illustration);
        $this->illustration = '' === $illustration ? null : $illustration;

        return $this;
    }

    #[ApiProperty(writable: false)]
    #[SerializedName('illustration')]
    #[Groups(['promise:read'])]
    public function getIllustrationUrl(): ?string
    {
        if (null === $this->illustration || '' === trim($this->illustration)) {
            return null;
        }

        return '/uploads/illustrations/'.$this->illustration;
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
    #[Groups(['promise:read'])]
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
            $source->setCampaignPromise($this);
        }

        return $this;
    }

    public function removeSource(Source $source): static
    {
        $this->sources->removeElement($source);

        return $this;
    }

    public function __toString(): string
    {
        return $this->title ?? 'Promesse';
    }
}
