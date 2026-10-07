<?php

namespace App\ApiPlatform;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\Controversy;
use App\Entity\ControversyItem;
use App\Entity\Person;
use App\Entity\Source;
use Doctrine\ORM\QueryBuilder;

final class VisibleOnlyExtension implements QueryCollectionExtensionInterface, QueryItemExtensionInterface
{
    /**
     * @var list<class-string>
     */
    private const RESOURCE_CLASSES = [
        Controversy::class,
        Person::class,
        ControversyItem::class,
        Source::class,
    ];

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
        if (!\in_array($resourceClass, self::RESOURCE_CLASSES, true)) {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];
        $queryBuilder->andWhere(\sprintf('%s.isVisible = true', $alias));
    }
}
