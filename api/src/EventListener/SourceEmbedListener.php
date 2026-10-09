<?php

namespace App\EventListener;

use App\Entity\Source;
use App\Service\EmbedResolver;
use App\Service\ResolvedEmbed;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;

#[AsEntityListener(event: Events::prePersist, method: 'prePersist', entity: Source::class)]
#[AsEntityListener(event: Events::preUpdate, method: 'preUpdate', entity: Source::class)]
final class SourceEmbedListener
{
    public function __construct(
        private EmbedResolver $embedResolver,
    ) {
    }

    public function prePersist(Source $source): void
    {
        $this->apply($source);
    }

    public function preUpdate(Source $source, PreUpdateEventArgs $event): void
    {
        if (!$event->hasChangedField('url')) {
            return;
        }

        $this->apply($source);

        $manager = $event->getObjectManager();
        if (!$manager instanceof EntityManagerInterface) {
            return;
        }

        $manager->getUnitOfWork()->recomputeSingleEntityChangeSet(
            $manager->getClassMetadata(Source::class),
            $source,
        );
    }

    private function apply(Source $source): void
    {
        $url = trim((string) $source->getUrl());
        if ('' === $url) {
            $source->applyResolvedEmbed(ResolvedEmbed::link());

            return;
        }

        if ($url !== $source->getUrl()) {
            $source->setUrl($url);
        }

        try {
            $source->applyResolvedEmbed($this->embedResolver->resolve($url));
        } catch (\Throwable) {
            // A provider outage must not abort the save. The URL is already stored.
        }
    }
}
