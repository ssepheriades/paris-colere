<?php

namespace App\ApiPlatform;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\CampaignPromise;
use App\Entity\Controversy;
use App\Entity\ControversyItem;
use App\Entity\KeyFigure;
use App\Entity\Person;
use App\Entity\Source;
use App\Security\ApiAccess;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final class VisibleOnlyExtension implements QueryCollectionExtensionInterface, QueryItemExtensionInterface
{
    /**
     * @var list<class-string>
     */
    private const RESOURCE_CLASSES = [
        CampaignPromise::class,
        Controversy::class,
        Person::class,
        ControversyItem::class,
        Source::class,
        KeyFigure::class,
    ];

    public function __construct(private readonly TokenStorageInterface $tokenStorage)
    {
    }

    /**
     * @param array<string, mixed> $context
     */
    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        $this->filterVisible($queryBuilder, $resourceClass);
    }

    /**
     * @param array<string, mixed> $identifiers
     * @param array<string, mixed> $context
     */
    public function applyToItem(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, array $identifiers, ?Operation $operation = null, array $context = []): void
    {
        $this->filterVisible($queryBuilder, $resourceClass);
    }

    private function filterVisible(QueryBuilder $queryBuilder, string $resourceClass): void
    {
        if (!\in_array($resourceClass, self::RESOURCE_CLASSES, true) || $this->isApiToken()) {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];
        $queryBuilder->andWhere(\sprintf('%s.isVisible = true', $alias));
    }

    private function isApiToken(): bool
    {
        $token = $this->tokenStorage->getToken();

        return null !== $token && \in_array(ApiAccess::ROLE, $token->getRoleNames(), true);
    }
}
