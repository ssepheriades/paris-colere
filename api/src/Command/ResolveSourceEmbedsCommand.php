<?php

namespace App\Command;

use App\Repository\SourceRepository;
use App\Service\EmbedResolver;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:resolve-source-embeds',
    description: 'Récupère oEmbed ou Open Graph pour les sources déjà enregistrées',
)]
class ResolveSourceEmbedsCommand extends Command
{
    public function __construct(
        private readonly SourceRepository $sources,
        private readonly EmbedResolver $resolver,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $resolved = 0;
        $bare = 0;

        foreach ($this->sources->findBy([], ['id' => 'ASC']) as $source) {
            $url = $source->getUrl();
            if (null === $url || '' === trim($url)) {
                continue;
            }

            $embed = $this->resolver->resolve($url);
            $source->applyResolvedEmbed($embed);
            if (null !== $embed->title || null !== $embed->externalId) {
                ++$resolved;
            } else {
                ++$bare;
            }
        }

        $this->entityManager->flush();
        $io->success(sprintf('%d source(s) renseignée(s), %d sans aperçu.', $resolved, $bare));

        return Command::SUCCESS;
    }
}
