<?php

namespace App\DataFixtures;

use App\Entity\LegalEntity;
use App\Entity\Party;
use App\Entity\Person;
use App\Entity\PersonLegalEntity;
use App\Enum\LegalEntityType;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ParisActorsFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $parties = [];
        foreach (ParisActorsCatalog::parties() as $data) {
            $parties[$data['name']] = $this->party($manager, $data['name'], $data['color']);
        }

        $legalEntities = [];
        foreach (ParisActorsCatalog::legalEntities() as $data) {
            $legalEntities[$data['name']] = $this->legalEntity(
                $manager,
                $data['name'],
                $data['shortDescription'],
                $data['website'],
            );
        }

        foreach (ParisActorsCatalog::people() as $data) {
            $this->person($manager, $data, $parties, $legalEntities);
        }

        $manager->flush();
    }

    private function party(ObjectManager $manager, string $name, string $color): Party
    {
        $this->limit($name, 64, 'Nom de parti');
        $this->limit($color, 16, 'Couleur de parti');

        $party = $manager->getRepository(Party::class)->findOneBy(['name' => $name]);
        if ($party instanceof Party) {
            return $party;
        }

        $party = new Party();
        $party->setName($name);
        $party->setColor($color);
        $manager->persist($party);

        return $party;
    }

    private function legalEntity(ObjectManager $manager, string $name, string $shortDescription, string $website): LegalEntity
    {
        $this->limit($name, 64, 'Nom de structure');
        $this->limit($shortDescription, 255, 'Résumé de structure');
        $this->limit($website, 255, 'Site de structure');

        $legalEntity = $manager->getRepository(LegalEntity::class)->findOneBy(['name' => $name]);
        if ($legalEntity instanceof LegalEntity) {
            return $legalEntity;
        }

        $legalEntity = new LegalEntity();
        $legalEntity->setName($name);
        $legalEntity->setShortDescription($shortDescription);
        $legalEntity->setWebsite($website);
        $legalEntity->setType(LegalEntityType::LocalAuthority);
        $manager->persist($legalEntity);

        return $legalEntity;
    }

    /**
     * @param array{
     *     firstname: string,
     *     lastname: string,
     *     shortDescription: string,
     *     bio: string,
     *     wikiUrl: ?string,
     *     parties: list<string>,
     *     mandates: list<array{legalEntity: string, position: string, start: string, end: ?string}>
     * } $data
     * @param array<string, Party>       $parties
     * @param array<string, LegalEntity> $legalEntities
     */
    private function person(ObjectManager $manager, array $data, array $parties, array $legalEntities): void
    {
        $this->limit($data['firstname'], 64, 'Prénom');
        $this->limit($data['lastname'], 64, 'Nom');
        $this->limit($data['shortDescription'], 128, 'Résumé de '.$data['firstname'].' '.$data['lastname']);
        if (null !== $data['wikiUrl']) {
            $this->limit($data['wikiUrl'], 255, 'Wiki de '.$data['firstname'].' '.$data['lastname']);
        }

        $person = $manager->getRepository(Person::class)->findOneBy([
            'firstname' => $data['firstname'],
            'lastname' => $data['lastname'],
        ]);

        if (!$person instanceof Person) {
            $person = new Person();
            $person->setFirstname($data['firstname']);
            $person->setLastname($data['lastname']);
            $person->setShortDescription($data['shortDescription']);
            $person->setBio($data['bio']);
            $person->setWikiUrl($data['wikiUrl']);
            $manager->persist($person);
        } else {
            $this->fillEmpty($person->getShortDescription(), $data['shortDescription'], $person->setShortDescription(...));
            $this->fillEmpty($person->getBio(), $data['bio'], $person->setBio(...));
            $this->fillEmpty($person->getWikiUrl(), $data['wikiUrl'], $person->setWikiUrl(...));
        }

        foreach ($data['parties'] as $partyName) {
            if (!isset($parties[$partyName])) {
                throw new \InvalidArgumentException(sprintf('Parti inconnu : %s.', $partyName));
            }

            $party = $parties[$partyName];
            if (!$person->getParty()->contains($party)) {
                $person->addParty($party);
            }
        }

        foreach ($data['mandates'] as $mandate) {
            if (!isset($legalEntities[$mandate['legalEntity']])) {
                throw new \InvalidArgumentException(sprintf('Structure inconnue : %s.', $mandate['legalEntity']));
            }

            $this->limit($mandate['position'], 255, 'Fonction de '.$data['firstname'].' '.$data['lastname']);
            if ($this->hasMandate($person, $mandate)) {
                continue;
            }

            $link = new PersonLegalEntity();
            $link->setPosition($mandate['position']);
            $link->setStartDate($this->date($mandate['start']));
            $link->setEndDate(null === $mandate['end'] ? null : $this->date($mandate['end']));
            $link->setLegalEntity($legalEntities[$mandate['legalEntity']]);
            $link->setPerson($person);
            $manager->persist($link);
        }
    }

    /**
     * @param array{legalEntity: string, position: string, start: string, end: ?string} $mandate
     */
    private function hasMandate(Person $person, array $mandate): bool
    {
        foreach ($person->getPersonLegalEntities() as $existing) {
            if ($existing->getLegalEntity()?->getName() !== $mandate['legalEntity']) {
                continue;
            }

            if ($existing->getPosition() !== $mandate['position']) {
                continue;
            }

            if ($existing->getStartDate()?->format('Y-m-d') === $mandate['start']) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param callable(?string): void $setter
     */
    private function fillEmpty(?string $current, ?string $value, callable $setter): void
    {
        if (null === $value || '' === $value) {
            return;
        }

        if (null !== $current && '' !== $current) {
            return;
        }

        $setter($value);
    }

    private function date(string $value): \DateTime
    {
        $date = \DateTime::createFromFormat('!Y-m-d', $value);
        if (false === $date) {
            throw new \InvalidArgumentException(sprintf('Date invalide : %s.', $value));
        }

        return $date;
    }

    private function limit(string $value, int $max, string $label): void
    {
        $length = mb_strlen($value);
        if ($length > $max) {
            throw new \InvalidArgumentException(sprintf('%s dépasse %d caractères (%d).', $label, $max, $length));
        }
    }
}
