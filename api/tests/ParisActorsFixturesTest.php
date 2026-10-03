<?php

namespace App\Tests;

use App\DataFixtures\ParisActorsCatalog;
use App\DataFixtures\ParisActorsFixtures;
use App\Entity\LegalEntity;
use App\Entity\Party;
use App\Entity\Person;
use App\Entity\PersonLegalEntity;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ParisActorsFixturesTest extends KernelTestCase
{
    public function testSecondLoadDoesNotDuplicate(): void
    {
        self::bootKernel();

        /** @var EntityManagerInterface $manager */
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $fixtures = new ParisActorsFixtures();

        $fixtures->load($manager);
        $counts = $this->counts($manager);

        $manager->clear();
        $fixtures->load($manager);

        self::assertSame($counts, $this->counts($manager));
        self::assertSame(\count(ParisActorsCatalog::parties()), $counts['parties']);
        self::assertSame(\count(ParisActorsCatalog::legalEntities()), $counts['legalEntities']);
        self::assertSame(\count(ParisActorsCatalog::people()), $counts['people']);
        self::assertSame($this->expectedMandates(), $counts['mandates']);

        $hidalgo = $this->person($manager, 'Anne', 'Hidalgo');
        self::assertSame('Ancienne Maire de Paris', $hidalgo->getShortDescription());
        self::assertCount(2, $hidalgo->getPersonLegalEntities());

        $gregoire = $this->person($manager, 'Emmanuel', 'Grégoire');
        self::assertSame('Maire de Paris', $gregoire->getShortDescription());
        self::assertNotNull($gregoire->getWikiUrl());
        self::assertCount(2, $gregoire->getPersonLegalEntities());

        self::assertCount(3, $this->person($manager, 'Patrick', 'Bloche')->getPersonLegalEntities());
        self::assertCount(3, $this->person($manager, 'Dominique', 'Versini')->getPersonLegalEntities());
        self::assertCount(2, $this->person($manager, 'David', 'Belliard')->getPersonLegalEntities());

        $brossat = $this->person($manager, 'Ian', 'Brossat');
        self::assertCount(2, $brossat->getPersonLegalEntities());
        $housing = null;
        foreach ($brossat->getPersonLegalEntities() as $mandate) {
            if ('2023-09-24' === $mandate->getEndDate()?->format('Y-m-d')) {
                $housing = $mandate;
            }
        }
        self::assertInstanceOf(PersonLegalEntity::class, $housing);
        self::assertSame('2014-04-05', $housing->getStartDate()?->format('Y-m-d'));

        $lamia = $this->person($manager, 'Lamia', 'El Aaraje');
        self::assertCount(1, $lamia->getPersonLegalEntities());
        self::assertNull($lamia->getPersonLegalEntities()->first()->getEndDate());
    }

    private function person(EntityManagerInterface $manager, string $firstname, string $lastname): Person
    {
        $person = $manager->getRepository(Person::class)->findOneBy([
            'firstname' => $firstname,
            'lastname' => $lastname,
        ]);
        self::assertInstanceOf(Person::class, $person);

        return $person;
    }

    /**
     * @return array{parties: int, legalEntities: int, people: int, mandates: int}
     */
    private function counts(EntityManagerInterface $manager): array
    {
        return [
            'parties' => $manager->getRepository(Party::class)->count([]),
            'legalEntities' => $manager->getRepository(LegalEntity::class)->count([]),
            'people' => $manager->getRepository(Person::class)->count([]),
            'mandates' => $manager->getRepository(PersonLegalEntity::class)->count([]),
        ];
    }

    private function expectedMandates(): int
    {
        $total = 0;
        foreach (ParisActorsCatalog::people() as $person) {
            $total += \count($person['mandates']);
        }

        return $total;
    }
}
