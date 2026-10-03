<?php

namespace App\DataFixtures;

use App\Entity\Theme;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ThemeFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        foreach (ParisThemesCatalog::themes() as $data) {
            $this->upsertTheme($manager, $data['name'], $data['color'], $data['shortDescription']);
        }

        $manager->flush();
    }

    private function upsertTheme(
        ObjectManager $manager,
        string $name,
        string $color,
        string $shortDescription,
    ): void {
        $this->limit($name, 32, 'Nom de thème');
        $this->limit($color, 16, 'Couleur de thème');
        $this->limit($shortDescription, 128, 'Description courte de thème');

        $theme = $manager->getRepository(Theme::class)->findOneBy(['name' => $name]);
        if (!$theme instanceof Theme) {
            $theme = new Theme();
            $theme->setName($name);
            $manager->persist($theme);
        }

        $theme->setColor($color);
        $theme->setShortDescription($shortDescription);
    }

    private function limit(string $value, int $max, string $label): void
    {
        $length = mb_strlen($value);
        if ($length > $max) {
            throw new \InvalidArgumentException(sprintf('%s dépasse %d caractères (%d).', $label, $max, $length));
        }
    }
}
