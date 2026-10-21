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
use App\Enum\CampaignPromiseResultType;
use App\Enum\CampaignPromiseStatus;
use App\Repository\CampaignPromiseRepository;
use App\Security\ApiAccess;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Serializer\Attribute\Context;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: CampaignPromiseRepository::class)]
#[Assert\Callback('validateResult')]
#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(),
        new Post(security: ApiAccess::WRITE),
        new Put(security: ApiAccess::WRITE),
        new Patch(security: ApiAccess::WRITE),
        new Delete(security: ApiAccess::WRITE),
    ],
    normalizationContext: ['groups' => ['promise:read']],
    denormalizationContext: ['groups' => ['api:write']],
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
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    #[Groups(['promise:read', 'api:write'])]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank]
    #[Groups(['promise:read', 'api:write'])]
    private ?string $quote = null;

    #[ORM\Column(length: 16, enumType: CampaignPromiseStatus::class)]
    #[Assert\NotNull]
    #[Groups(['promise:read', 'api:write'])]
    private ?CampaignPromiseStatus $status = null;

    #[ORM\Column(length: 16, nullable: true, enumType: CampaignPromiseResultType::class)]
    #[Groups(['promise:read', 'api:write'])]
    private ?CampaignPromiseResultType $resultType = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 14, scale: 2, nullable: true)]
    #[Groups(['api:write'])]
    private ?string $resultValue = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 14, scale: 2, nullable: true)]
    #[Groups(['api:write'])]
    private ?string $objective = null;

    #[ORM\Column(length: 32, nullable: true)]
    #[Assert\Length(max: 32)]
    #[Groups(['promise:read', 'api:write'])]
    private ?string $resultLabel = null;

    #[ORM\Column]
    #[Assert\NotNull]
    #[Groups(['promise:read', 'api:write'])]
    private ?bool $isVisible = false;

    #[ORM\Column(length: 255, nullable: true)]
    #[ApiProperty(readable: false, writable: false)]
    private ?string $illustration = null;

    /**
     * @var Collection<int, Theme>
     */
    #[ORM\ManyToMany(targetEntity: Theme::class, inversedBy: 'campaignPromises')]
    #[Groups(['promise:read', 'api:write'])]
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

    public function getResultType(): ?CampaignPromiseResultType
    {
        return $this->resultType;
    }

    public function setResultType(?CampaignPromiseResultType $resultType): static
    {
        $this->resultType = $resultType;

        return $this;
    }

    #[Groups(['promise:read'])]
    public function getResultValue(): ?float
    {
        return $this->decimal($this->resultValue);
    }

    public function setResultValue(float|int|string|null $resultValue): static
    {
        $this->resultValue = $this->normalizeDecimal($resultValue);

        return $this;
    }

    #[Groups(['promise:read'])]
    #[Context([AbstractObjectNormalizer::SKIP_NULL_VALUES => false])]
    public function getObjective(): ?float
    {
        if (CampaignPromiseResultType::Absolute !== $this->resultType) {
            return null;
        }

        return $this->decimal($this->objective);
    }

    public function setObjective(float|int|string|null $objective): static
    {
        $this->objective = $this->normalizeDecimal($objective);

        return $this;
    }

    public function getResultLabel(): ?string
    {
        return $this->resultLabel;
    }

    public function setResultLabel(?string $resultLabel): static
    {
        $resultLabel = null === $resultLabel ? null : trim($resultLabel);
        $this->resultLabel = '' === $resultLabel ? null : $resultLabel;

        return $this;
    }

    public function validateResult(ExecutionContextInterface $context): void
    {
        if (null === $this->resultType) {
            if (null !== $this->resultValue || null !== $this->objective || null !== $this->resultLabel) {
                $context->buildViolation('Une promesse sans chiffre ne peut pas avoir de valeur, d’objectif ou de libellé.')
                    ->atPath('resultType')
                    ->addViolation();
            }

            return;
        }

        if (null === $this->resultValue) {
            $context->buildViolation('Indiquez la valeur atteinte.')
                ->atPath('resultValue')
                ->addViolation();
        }

        if (CampaignPromiseResultType::Percentage === $this->resultType) {
            $value = $this->decimal($this->resultValue);
            if (null !== $value && ($value < 0 || $value > 100)) {
                $context->buildViolation('Le pourcentage doit être compris entre 0 et 100.')
                    ->atPath('resultValue')
                    ->addViolation();
            }

            if (null !== $this->objective || null !== $this->resultLabel) {
                $context->buildViolation('Un pourcentage n’a ni objectif ni libellé.')
                    ->atPath('resultType')
                    ->addViolation();
            }

            return;
        }

        if (null === $this->objective) {
            $context->buildViolation('Indiquez l’objectif.')
                ->atPath('objective')
                ->addViolation();
        }
    }

    private function decimal(?string $value): ?float
    {
        return null === $value ? null : (float) $value;
    }

    private function normalizeDecimal(float|int|string|null $value): ?string
    {
        if (null === $value || '' === $value) {
            return null;
        }

        return number_format((float) $value, 2, '.', '');
    }

    public function isVisible(): ?bool
    {
        return $this->isVisible;
    }

    #[Groups(['promise:read'])]
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
