<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\ThemeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: ThemeRepository::class)]
#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(),
    ],
    normalizationContext: ['groups' => ['theme:read']],
)]
class Theme
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['theme:read', 'controversy:read', 'promise:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 32)]
    #[Groups(['theme:read', 'controversy:read', 'promise:read'])]
    private ?string $name = null;

    #[ORM\Column(length: 16, nullable: true)]
    #[Groups(['theme:read', 'controversy:read', 'promise:read'])]
    private ?string $color = null;

    #[ORM\Column(length: 64, nullable: true)]
    #[Groups(['theme:read', 'controversy:read', 'promise:read'])]
    private ?string $icon = null;

    #[ORM\Column(length: 128)]
    #[Groups(['theme:read', 'controversy:read', 'promise:read'])]
    private ?string $shortDescription = null;

    /**
     * @var Collection<int, Controversy>
     */
    #[ORM\ManyToMany(targetEntity: Controversy::class, mappedBy: 'theme')]
    private Collection $controversies;

    /**
     * @var Collection<int, CampaignPromise>
     */
    #[ORM\ManyToMany(targetEntity: CampaignPromise::class, mappedBy: 'theme')]
    private Collection $campaignPromises;

    public function __construct()
    {
        $this->controversies = new ArrayCollection();
        $this->campaignPromises = new ArrayCollection();
    }

    public function getId(): ?int
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

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(?string $color): static
    {
        $this->color = $color;

        return $this;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function setIcon(?string $icon): static
    {
        $this->icon = $icon;

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
     * @return Collection<int, Controversy>
     */
    public function getControversies(): Collection
    {
        return $this->controversies;
    }

    public function addControversy(Controversy $controversy): static
    {
        if (!$this->controversies->contains($controversy)) {
            $this->controversies->add($controversy);
            $controversy->addTheme($this);
        }

        return $this;
    }

    public function removeControversy(Controversy $controversy): static
    {
        if ($this->controversies->removeElement($controversy)) {
            $controversy->removeTheme($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, CampaignPromise>
     */
    public function getCampaignPromises(): Collection
    {
        return $this->campaignPromises;
    }

    public function addCampaignPromise(CampaignPromise $campaignPromise): static
    {
        if (!$this->campaignPromises->contains($campaignPromise)) {
            $this->campaignPromises->add($campaignPromise);
            $campaignPromise->addTheme($this);
        }

        return $this;
    }

    public function removeCampaignPromise(CampaignPromise $campaignPromise): static
    {
        if ($this->campaignPromises->removeElement($campaignPromise)) {
            $campaignPromise->removeTheme($this);
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->name ?? 'Thème';
    }
}
