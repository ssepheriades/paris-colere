<?php

namespace App\DataFixtures;

/**
 * Élus parisiens chargés par ParisActorsFixtures.
 * Fonctions au 29 mars 2026, d'après paris.fr et la composition du Conseil de Paris.
 */
final class ParisActorsCatalog
{
    public const CITY = 'Mairie de Paris';

    private const PS = 'Parti Socialiste';
    private const PCF = 'Parti Communiste Français';
    private const LR = 'Les Républicains';
    private const LFI = 'La France Insoumise';
    private const ECO = 'Les Écologistes';
    private const PP = 'Place publique';
    private const HORIZONS = 'Horizons';
    private const MODEM = 'MoDem';
    private const APRES = "L'Après";

    /**
     * @return list<array{name: string, color: string}>
     */
    public static function parties(): array
    {
        return [
            ['name' => self::PS, 'color' => '#ff0066'],
            ['name' => self::LFI, 'color' => '#990059'],
            ['name' => self::LR, 'color' => '#000875'],
            ['name' => self::PCF, 'color' => '#ff0000'],
            ['name' => self::ECO, 'color' => '#00a86b'],
            ['name' => self::PP, 'color' => '#1a1a1a'],
            ['name' => 'Renaissance', 'color' => '#ffcc00'],
            ['name' => self::HORIZONS, 'color' => '#003d7a'],
            ['name' => self::MODEM, 'color' => '#ff6600'],
            ['name' => 'UDI', 'color' => '#00a0e3'],
            ['name' => self::APRES, 'color' => '#6b2d5b'],
        ];
    }

    /**
     * @return list<array{name: string, shortDescription: string, website: string}>
     */
    public static function legalEntities(): array
    {
        $entities = [
            [
                'name' => self::CITY,
                'shortDescription' => 'Mairie de Paris',
                'website' => 'https://www.paris.fr',
            ],
            [
                'name' => 'Mairie de Paris Centre',
                'shortDescription' => 'Mairie de Paris Centre',
                'website' => 'https://www.paris.fr',
            ],
        ];

        foreach ([5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20] as $number) {
            $name = self::arrondissement($number);
            $entities[] = [
                'name' => $name,
                'shortDescription' => $name,
                'website' => 'https://www.paris.fr',
            ];
        }

        return $entities;
    }

    /**
     * @return list<array{
     *     firstname: string,
     *     lastname: string,
     *     shortDescription: string,
     *     bio: string,
     *     wikiUrl: ?string,
     *     parties: list<string>,
     *     mandates: list<array{legalEntity: string, position: string, start: string, end: ?string}>
     * }>
     */
    public static function people(): array
    {
        return [
            ...self::alreadyKnown(),
            ...self::deputies(),
            ...self::arrondissementMayors(),
            ...self::groupPresidents(),
        ];
    }

    private static function arrondissement(int $number): string
    {
        return sprintf('Mairie du %de arrondissement', $number);
    }

    /**
     * @param list<string>                                                                                              $parties
     * @param list<array{legalEntity: string, position: string, start: string, end: ?string}>                           $mandates
     *
     * @return array{firstname: string, lastname: string, shortDescription: string, bio: string, wikiUrl: ?string, parties: list<string>, mandates: list<array{legalEntity: string, position: string, start: string, end: ?string}>}
     */
    private static function person(
        string $firstname,
        string $lastname,
        string $shortDescription,
        string $bio,
        ?string $wikiUrl,
        array $parties,
        array $mandates,
    ): array {
        return [
            'firstname' => $firstname,
            'lastname' => $lastname,
            'shortDescription' => $shortDescription,
            'bio' => $bio,
            'wikiUrl' => $wikiUrl,
            'parties' => $parties,
            'mandates' => $mandates,
        ];
    }

    /**
     * @return array{legalEntity: string, position: string, start: string, end: ?string}
     */
    private static function mandate(string $legalEntity, string $position, string $start, ?string $end = null): array
    {
        return [
            'legalEntity' => $legalEntity,
            'position' => $position,
            'start' => $start,
            'end' => $end,
        ];
    }

    /**
     * @return list<array{firstname: string, lastname: string, shortDescription: string, bio: string, wikiUrl: ?string, parties: list<string>, mandates: list<array{legalEntity: string, position: string, start: string, end: ?string}>}>
     */
    private static function alreadyKnown(): array
    {
        return [
            self::person(
                'Anne',
                'Hidalgo',
                'Ancienne Maire de Paris',
                'Anne Hidalgo est maire de Paris de 2014 à 2026, après avoir été première adjointe de Bertrand Delanoë de 2001 à 2014. Emmanuel Grégoire lui succède à l\'Hôtel de Ville le 29 mars 2026.',
                'https://fr.wikipedia.org/wiki/Anne_Hidalgo',
                [self::PS],
                [
                    self::mandate(self::CITY, 'Première adjointe au maire de Paris', '2001-03-25', '2014-04-05'),
                    self::mandate(self::CITY, 'Maire de Paris', '2014-04-05', '2026-03-29'),
                ],
            ),
            self::person(
                'Emmanuel',
                'Grégoire',
                'Maire de Paris',
                'Emmanuel Grégoire est maire de Paris depuis le 29 mars 2026. Il a été premier adjoint d\'Anne Hidalgo de septembre 2018 à juillet 2024.',
                'https://fr.wikipedia.org/wiki/Emmanuel_Grégoire',
                [self::PS],
                [
                    self::mandate(self::CITY, 'Premier adjoint à la maire de Paris', '2018-09-24', '2024-07-08'),
                    self::mandate(self::CITY, 'Maire de Paris', '2026-03-29'),
                ],
            ),
            self::person(
                'Patrick',
                'Bloche',
                'Ancien premier adjoint à la maire de Paris',
                'Patrick Bloche, membre du Parti socialiste, a été député de Paris de 1997 à 2017 et maire du 11e arrondissement de 2008 à 2014. Adjoint d\'Anne Hidalgo à l\'éducation à partir d\'octobre 2017, il devient premier adjoint en juillet 2024 et le reste jusqu\'au 29 mars 2026.',
                'https://fr.wikipedia.org/wiki/Patrick_Bloche',
                [self::PS],
                [
                    self::mandate(self::arrondissement(11), 'Maire', '2008-03-29', '2014-04-13'),
                    self::mandate(self::CITY, 'Adjoint à l\'éducation, à la petite enfance, aux familles et aux nouveaux apprentissages', '2017-10-06', '2024-07-08'),
                    self::mandate(self::CITY, 'Premier adjoint à la maire de Paris, chargé de l\'éducation, de la petite enfance, des familles, du Conseil de Paris et des relations avec les arrondissements', '2024-07-08', '2026-03-29'),
                ],
            ),
            self::person(
                'Dominique',
                'Versini',
                'Ancienne adjointe à la protection de l\'enfance',
                'Dominique Versini est adjointe d\'Anne Hidalgo de 2014 à 2026, d\'abord chargée des solidarités, puis des droits de l\'enfant et de la protection de l\'enfance. Elle a dirigé le Samu social de Paris, été secrétaire d\'État à la lutte contre la précarité et l\'exclusion de 2002 à 2004, puis Défenseure des enfants de 2006 à 2011.',
                'https://fr.wikipedia.org/wiki/Dominique_Versini',
                [self::PS],
                [
                    self::mandate(self::CITY, 'Adjointe aux solidarités, aux familles, à la petite enfance, à la protection de l\'enfance, à la lutte contre l\'exclusion et aux personnes âgées', '2014-04-05', '2017-10-06'),
                    self::mandate(self::CITY, 'Adjointe aux solidarités, à la lutte contre l\'exclusion, à l\'accueil des réfugiés et à la protection de l\'enfance', '2017-10-06', '2020-07-03'),
                    self::mandate(self::CITY, 'Adjointe aux droits de l\'enfant et à la protection de l\'enfance', '2020-07-03', '2026-03-29'),
                ],
            ),
        ];
    }

    /**
     * @return list<array{firstname: string, lastname: string, shortDescription: string, bio: string, wikiUrl: ?string, parties: list<string>, mandates: list<array{legalEntity: string, position: string, start: string, end: ?string}>}>
     */
    private static function deputies(): array
    {
        $start = '2026-03-29';

        return [
            self::person(
                'Lamia',
                'El Aaraje',
                'Première adjointe au maire de Paris',
                'Lamia El Aaraje, membre du Parti socialiste, est conseillère de Paris depuis 2020 et première secrétaire fédérale du PS à Paris depuis 2023. Adjointe d\'Anne Hidalgo à partir de novembre 2022, elle devient première adjointe d\'Emmanuel Grégoire le 29 mars 2026.',
                'https://fr.wikipedia.org/wiki/Lamia_El_Aaraje',
                [self::PS],
                [self::mandate(self::CITY, 'Première adjointe — coordination avec les arrondissements, grands projets du mandat, sécurité du quotidien et Grand Paris', $start)],
            ),
            self::person(
                'Anne-Claire',
                'Boux',
                'Adjointe aux affaires scolaires et à la petite enfance',
                'Anne-Claire Boux, membre des Écologistes, est adjointe au maire de Paris chargée des affaires scolaires et de la petite enfance depuis le 29 mars 2026. Elle siège au Conseil de Paris dans le groupe écologiste.',
                null,
                [self::ECO],
                [self::mandate(self::CITY, 'Adjointe aux affaires scolaires et à la petite enfance', $start)],
            ),
            self::person(
                'Jacques',
                'Baudrier',
                'Adjoint au logement',
                'Jacques Baudrier, membre du Parti communiste français, est adjoint au maire de Paris chargé du logement, de la rénovation thermique, de l\'encadrement des loyers et de la défense des locataires depuis le 29 mars 2026. Il siège au groupe communiste du Conseil de Paris.',
                null,
                [self::PCF],
                [self::mandate(self::CITY, 'Adjoint au logement, à la rénovation thermique, à l\'encadrement des loyers et à la défense des locataires', $start)],
            ),
            self::person(
                'Fatoumata',
                'Koné',
                'Adjointe aux solidarités',
                'Fatoumata Koné, membre des Écologistes, est adjointe au maire de Paris chargée des solidarités, de la lutte contre les inégalités et de la lutte contre l\'exclusion depuis le 29 mars 2026. Elle siège au groupe écologiste du Conseil de Paris.',
                'https://fr.wikipedia.org/wiki/Fatoumata_Koné_(femme_politique)',
                [self::ECO],
                [self::mandate(self::CITY, 'Adjointe aux solidarités, à la lutte contre les inégalités et à la lutte contre l\'exclusion', $start)],
            ),
            self::person(
                'Alice',
                'Timsit',
                'Adjointe à la transition écologique',
                'Alice Timsit, membre des Écologistes, est adjointe au maire de Paris chargée de la transition écologique, du Plan climat, de l\'eau et de l\'énergie depuis le 29 mars 2026. Elle siège au groupe écologiste du Conseil de Paris.',
                null,
                [self::ECO],
                [self::mandate(self::CITY, 'Adjointe à la transition écologique, au Plan climat, à l\'eau et à l\'énergie', $start)],
            ),
            self::person(
                'Florian',
                'Sitbon',
                'Adjoint à la culture',
                'Florian Sitbon, metteur en scène et membre du Parti socialiste, est adjoint au maire de Paris chargé de la culture, de la souveraineté culturelle et des médias libres depuis le 29 mars 2026. Il siège au groupe socialiste du Conseil de Paris.',
                'https://fr.wikipedia.org/wiki/Florian_Sitbon',
                [self::PS],
                [self::mandate(self::CITY, 'Adjoint à la culture, à la souveraineté culturelle et aux médias libres', $start)],
            ),
            self::person(
                'Pierre',
                'Lombard',
                'Adjoint à la propreté',
                'Pierre Lombard, membre de Place publique, est adjoint au maire de Paris chargé de la propreté, de la réduction des déchets et de l\'économie circulaire depuis le 29 mars 2026. Il siège au groupe socialiste et divers gauche du Conseil de Paris.',
                null,
                [self::PP],
                [self::mandate(self::CITY, 'Adjoint à la propreté, à la réduction des déchets et à l\'économie circulaire', $start)],
            ),
            self::person(
                'Marine',
                'Rosset',
                'Adjointe à la vie associative et à la démocratie de proximité',
                'Marine Rosset, membre du Parti socialiste, est adjointe au maire de Paris chargée de la vie associative, du débat public, du budget participatif et de la démocratie de proximité depuis le 29 mars 2026. Elle siège au groupe socialiste du Conseil de Paris.',
                'https://fr.wikipedia.org/wiki/Marine_Rosset',
                [self::PS],
                [self::mandate(self::CITY, 'Adjointe à la vie associative, au débat public, aux conventions et référendums d\'initiative citoyenne, au budget participatif et à la démocratie de proximité', $start)],
            ),
            self::person(
                'Aminata',
                'Niakaté',
                'Adjointe à la végétalisation et à la biodiversité',
                'Aminata Niakaté, porte-parole des Écologistes, est conseillère de Paris depuis 2020. Le 29 mars 2026, elle devient adjointe au maire de Paris chargée de la végétalisation, de la biodiversité et de la condition animale.',
                'https://fr.wikipedia.org/wiki/Aminata_Niakaté',
                [self::ECO],
                [self::mandate(self::CITY, 'Adjointe à la végétalisation, à la biodiversité et à la condition animale', $start)],
            ),
            self::person(
                'Johanne',
                'Kouassi',
                'Adjointe aux finances',
                'Johanne Kouassi, membre du Parti socialiste, est adjointe au maire de Paris chargée des finances et du pilotage des SEM et SPL depuis le 29 mars 2026. Elle siège au groupe socialiste du Conseil de Paris.',
                null,
                [self::PS],
                [self::mandate(self::CITY, 'Adjointe aux finances et au pilotage des SEM et SPL', $start)],
            ),
            self::person(
                'Karim',
                'Ziady',
                'Adjoint aux quartiers populaires',
                'Karim Ziady, membre du Parti socialiste, est adjoint au maire de Paris chargé des quartiers populaires et de la politique de la ville depuis le 29 mars 2026. Il siège au groupe socialiste du Conseil de Paris.',
                null,
                [self::PS],
                [self::mandate(self::CITY, 'Adjoint aux quartiers populaires et à la politique de la ville', $start)],
            ),
            self::person(
                'Richard',
                'Bouigue',
                'Adjoint au développement économique',
                'Richard Bouigue, membre du Parti socialiste, est adjoint au maire de Paris chargé du développement économique, de l\'attractivité et de l\'innovation depuis le 29 mars 2026. Il siège au groupe socialiste du Conseil de Paris.',
                null,
                [self::PS],
                [self::mandate(self::CITY, 'Adjoint au développement économique, au dialogue avec les entreprises, à l\'attractivité et à l\'innovation', $start)],
            ),
            self::person(
                'Azadeh',
                'Akrami-Castanon',
                'Adjointe à l\'accessibilité et à l\'inclusion',
                'Azadeh Akrami-Castanon, membre des Écologistes, est adjointe au maire de Paris chargée de l\'accessibilité, de l\'inclusion et des personnes en situation de handicap depuis le 29 mars 2026. Elle siège au groupe écologiste du Conseil de Paris.',
                null,
                [self::ECO],
                [self::mandate(self::CITY, 'Adjointe à l\'accessibilité, à l\'inclusion et aux personnes en situation de handicap', $start)],
            ),
            self::person(
                'Dan',
                'Lert',
                'Adjoint aux transports et aux mobilités',
                'Dan Lert, membre des Écologistes, est adjoint au maire de Paris chargé des transports, des mobilités, du plan piéton, du plan vélo et de la logistique urbaine depuis le 29 mars 2026. Il siège au groupe écologiste du Conseil de Paris.',
                null,
                [self::ECO],
                [self::mandate(self::CITY, 'Adjoint aux transports, aux mobilités, au plan piéton, au plan vélo et à la logistique urbaine', $start)],
            ),
            self::person(
                'Barbara',
                'Gomes',
                'Adjointe à l\'égalité femmes-hommes',
                'Barbara Gomes, membre du Parti communiste français, est adjointe au maire de Paris chargée de l\'égalité femmes-hommes, de la lutte contre le travail précaire et de l\'économie des plateformes depuis le 29 mars 2026. Elle siège au groupe communiste du Conseil de Paris.',
                null,
                [self::PCF],
                [self::mandate(self::CITY, 'Adjointe à l\'égalité femmes-hommes, à la lutte contre le travail précaire et à l\'économie des plateformes', $start)],
            ),
            self::person(
                'Mam\'s',
                'Yaffa',
                'Adjoint à la jeunesse',
                'Mam\'s Yaffa, membre des Écologistes, est adjoint au maire de Paris chargé de la jeunesse, de l\'éducation populaire et de l\'égalité républicaine depuis le 29 mars 2026. Il siège au groupe écologiste du Conseil de Paris.',
                null,
                [self::ECO],
                [self::mandate(self::CITY, 'Adjoint à la jeunesse, à l\'éducation populaire et à l\'égalité républicaine', $start)],
            ),
            self::person(
                'Audrey',
                'Pulvar',
                'Adjointe aux relations internationales',
                'Audrey Pulvar, journaliste et conseillère régionale d\'Île-de-France, est adjointe au maire de Paris chargée des relations internationales, européennes et de la francophonie depuis le 29 mars 2026. Elle siège au Conseil de Paris comme divers gauche.',
                'https://fr.wikipedia.org/wiki/Audrey_Pulvar',
                [],
                [self::mandate(self::CITY, 'Adjointe aux relations internationales, européennes et à la francophonie', $start)],
            ),
            self::person(
                'Maxime',
                'des Gayets',
                'Adjoint au Conseil de Paris',
                'Maxime des Gayets, membre du Parti socialiste, est conseiller régional d\'Île-de-France depuis 2015. Le 29 mars 2026, il devient adjoint au maire de Paris chargé du Conseil de Paris, de la simplification de l\'action publique, de l\'évaluation des politiques publiques et de la résilience.',
                'https://fr.wikipedia.org/wiki/Maxime_des_Gayets',
                [self::PS],
                [self::mandate(self::CITY, 'Adjoint au Conseil de Paris, à la simplification de l\'action publique, à l\'évaluation des politiques publiques et à la résilience', $start)],
            ),
            self::person(
                'Carine',
                'Rolland',
                'Adjointe aux ressources humaines',
                'Carine Rolland, membre du Parti socialiste, est adjointe au maire de Paris chargée des ressources humaines et du dialogue social depuis le 29 mars 2026. Elle siège au groupe socialiste du Conseil de Paris.',
                null,
                [self::PS],
                [self::mandate(self::CITY, 'Adjointe aux ressources humaines et au dialogue social', $start)],
            ),
            self::person(
                'Maxime',
                'Sauvage',
                'Adjoint au sport',
                'Maxime Sauvage, membre du Parti socialiste, est adjoint au maire de Paris chargé du sport, des équipements sportifs et de l\'égalité d\'accès au sport depuis le 29 mars 2026. Il siège au groupe socialiste du Conseil de Paris.',
                null,
                [self::PS],
                [self::mandate(self::CITY, 'Adjoint au sport, aux équipements sportifs et à l\'égalité d\'accès au sport', $start)],
            ),
            self::person(
                'Mélody',
                'Tonolli',
                'Adjointe à l\'université et à la recherche',
                'Mélody Tonolli, membre des Écologistes, est adjointe au maire de Paris chargée de l\'université, de la recherche, de la vie étudiante et de l\'économie de la connaissance depuis le 29 mars 2026. Elle siège au groupe écologiste du Conseil de Paris.',
                null,
                [self::ECO],
                [self::mandate(self::CITY, 'Adjointe à l\'université, à la recherche, à la culture du savoir, à la vie étudiante et à l\'économie de la connaissance', $start)],
            ),
            self::person(
                'François',
                'Vauglin',
                'Adjoint à l\'urbanisme et à l\'architecture',
                'François Vauglin, membre du Parti socialiste, a été maire du 11e arrondissement de 2014 à 2026. Le 29 mars 2026, il devient adjoint au maire de Paris chargé de l\'urbanisme et de l\'architecture.',
                'https://fr.wikipedia.org/wiki/François_Vauglin',
                [self::PS],
                [self::mandate(self::CITY, 'Adjoint à l\'urbanisme et à l\'architecture', $start)],
            ),
            self::person(
                'Annah',
                'Bikouloulou',
                'Adjointe à l\'égalité et aux droits humains',
                'Annah Bikouloulou, membre des Écologistes, est adjointe au maire de Paris chargée de l\'égalité, des droits humains et de la lutte contre les discriminations depuis le 29 mars 2026. Elle siège au groupe écologiste du Conseil de Paris.',
                null,
                [self::ECO],
                [self::mandate(self::CITY, 'Adjointe à l\'égalité, aux droits humains, à la lutte contre le racisme, l\'antisémitisme et les LGBTQI+phobies et à la lutte contre les discriminations', $start)],
            ),
            self::person(
                'Antoine',
                'Guillou',
                'Adjoint aux espaces verts et à l\'alimentation durable',
                'Antoine Guillou, membre du Parti socialiste, est adjoint au maire de Paris chargé des espaces verts et bois, de l\'alimentation durable et des affaires funéraires depuis le 29 mars 2026. Il siège au groupe socialiste du Conseil de Paris.',
                null,
                [self::PS],
                [self::mandate(self::CITY, 'Adjoint aux espaces verts et bois, à l\'alimentation durable, aux circuits courts, à l\'Axe Seine et aux canaux, et aux affaires funéraires', $start)],
            ),
            self::person(
                'Yasmina',
                'Merzi',
                'Adjointe à la protection de l\'enfance',
                'Yasmina Merzi, membre du Parti socialiste, est adjointe au maire de Paris chargée de l\'aide sociale à l\'enfance et de la protection de l\'enfance depuis le 29 mars 2026. Elle siège au groupe socialiste du Conseil de Paris.',
                null,
                [self::PS],
                [self::mandate(self::CITY, 'Adjointe à l\'aide sociale à l\'enfance et à la protection de l\'enfance', $start)],
            ),
            self::person(
                'Adji',
                'Ahoudian',
                'Adjoint à l\'hébergement d\'urgence',
                'Adji Ahoudian, membre du Parti socialiste, est adjoint au maire de Paris chargé de l\'hébergement d\'urgence et de la protection des réfugiés depuis le 29 mars 2026. Il siège au groupe socialiste du Conseil de Paris.',
                null,
                [self::PS],
                [self::mandate(self::CITY, 'Adjoint à l\'hébergement d\'urgence et à la protection des réfugiés', $start)],
            ),
            self::person(
                'Anouch',
                'Toranian',
                'Adjointe au commerce et à l\'artisanat',
                'Anouch Toranian, membre du Parti socialiste, est adjointe au maire de Paris chargée du commerce, de l\'artisanat et des professions libérales depuis le 29 mars 2026. Elle siège au groupe socialiste du Conseil de Paris.',
                null,
                [self::PS],
                [self::mandate(self::CITY, 'Adjointe au commerce, à l\'artisanat et aux professions libérales', $start)],
            ),
            self::person(
                'Thomas',
                'Chevandier',
                'Adjoint aux espaces publics',
                'Thomas Chevandier, membre du Parti socialiste, est adjoint au maire de Paris chargé des espaces publics, de l\'aménagement et de la coordination des chantiers depuis le 29 mars 2026. Il siège au groupe socialiste du Conseil de Paris.',
                null,
                [self::PS],
                [self::mandate(self::CITY, 'Adjoint aux espaces publics, à l\'aménagement et à la coordination des chantiers', $start)],
            ),
            self::person(
                'Agnès',
                'Bertrand',
                'Adjointe à la laïcité',
                'Agnès Bertrand, membre du Parti socialiste, est adjointe au maire de Paris chargée de la laïcité, des principes républicains et du dialogue avec les cultes depuis le 29 mars 2026. Elle siège au groupe socialiste du Conseil de Paris.',
                null,
                [self::PS],
                [self::mandate(self::CITY, 'Adjointe à la laïcité, aux principes républicains et au dialogue avec les cultes', $start)],
            ),
            self::person(
                'Pierre',
                'Rabadan',
                'Adjoint au tourisme et à la vie nocturne',
                'Pierre Rabadan, ancien joueur de rugby, est adjoint au maire de Paris chargé du tourisme et de la vie nocturne depuis le 29 mars 2026. Il siège au Conseil de Paris comme divers gauche.',
                'https://fr.wikipedia.org/wiki/Pierre_Rabadan',
                [],
                [self::mandate(self::CITY, 'Adjoint au tourisme et à la vie nocturne', $start)],
            ),
            self::person(
                'Laurence',
                'Patrice',
                'Adjointe au monde combattant et aux mémoires',
                'Laurence Patrice, membre du Parti communiste français, est adjointe au maire de Paris chargée du monde combattant et des histoires et mémoires de Paris depuis le 29 mars 2026. Elle siège au groupe communiste du Conseil de Paris.',
                null,
                [self::PCF],
                [self::mandate(self::CITY, 'Adjointe au monde combattant, aux histoires et mémoires de Paris, et référente Défense', $start)],
            ),
            self::person(
                'Antoine',
                'Alibert',
                'Adjoint à la santé',
                'Antoine Alibert, membre des Écologistes, est adjoint au maire de Paris chargé de la santé publique et environnementale et du lien avec l\'AP-HP depuis le 29 mars 2026. Il siège au groupe écologiste du Conseil de Paris.',
                null,
                [self::ECO],
                [self::mandate(self::CITY, 'Adjoint à la santé, à la santé publique et environnementale, à la lutte contre les pollutions et au lien avec l\'AP-HP', $start)],
            ),
            self::person(
                'Nicolas',
                'Bonnet-Oulaldj',
                'Adjoint à l\'emploi et à l\'insertion',
                'Nicolas Bonnet-Oulaldj, membre du Parti communiste français, est adjoint au maire de Paris chargé de l\'emploi et de l\'insertion depuis le 29 mars 2026. Il siège au groupe communiste du Conseil de Paris.',
                null,
                [self::PCF],
                [self::mandate(self::CITY, 'Adjoint à l\'emploi et à l\'insertion', $start)],
            ),
            self::person(
                'Amina',
                'Bouri',
                'Adjointe à l\'économie sociale et solidaire',
                'Amina Bouri, membre des Écologistes, est adjointe au maire de Paris chargée de l\'économie sociale et solidaire depuis le 29 mars 2026. Elle siège au groupe écologiste du Conseil de Paris.',
                null,
                [self::ECO],
                [self::mandate(self::CITY, 'Adjointe à l\'économie sociale et solidaire', $start)],
            ),
            self::person(
                'Laurent',
                'Sorel',
                'Adjoint à l\'Outre-mer',
                'Laurent Sorel, membre de L\'Après, est adjoint au maire de Paris chargé de l\'Outre-mer depuis le 29 mars 2026. Il siège au groupe écologiste et social du Conseil de Paris.',
                null,
                [self::APRES],
                [self::mandate(self::CITY, 'Adjoint à l\'Outre-mer', $start)],
            ),
            self::person(
                'Maxime',
                'Crosnier',
                'Adjoint aux seniors',
                'Maxime Crosnier, membre des Écologistes, est adjoint au maire de Paris chargé des seniors et des enjeux démographiques depuis le 29 mars 2026. Il siège au groupe écologiste du Conseil de Paris.',
                null,
                [self::ECO],
                [self::mandate(self::CITY, 'Adjoint aux seniors et aux enjeux démographiques', $start)],
            ),
        ];
    }

    /**
     * @return list<array{firstname: string, lastname: string, shortDescription: string, bio: string, wikiUrl: ?string, parties: list<string>, mandates: list<array{legalEntity: string, position: string, start: string, end: ?string}>}>
     */
    private static function arrondissementMayors(): array
    {
        $start = '2026-03-29';

        return [
            self::person(
                'Ariel',
                'Weil',
                'Maire de Paris Centre',
                'Ariel Weil, membre du Parti socialiste et de La Convention, est maire du secteur Paris Centre. Il siège au groupe socialiste et divers gauche du Conseil de Paris.',
                'https://fr.wikipedia.org/wiki/Ariel_Weil',
                [self::PS],
                [self::mandate('Mairie de Paris Centre', 'Maire', $start)],
            ),
            self::person(
                'Florence',
                'Berthout',
                'Maire du 5e arrondissement',
                'Florence Berthout, membre d\'Horizons, est maire du 5e arrondissement et coprésidente du groupe Paris apaisé au Conseil de Paris.',
                'https://fr.wikipedia.org/wiki/Florence_Berthout',
                [self::HORIZONS],
                [self::mandate(self::arrondissement(5), 'Maire', $start)],
            ),
            self::person(
                'Jean-Pierre',
                'Lecoq',
                'Maire du 6e arrondissement',
                'Jean-Pierre Lecoq, membre des Républicains, est maire du 6e arrondissement. Il siège au groupe Paris Liberté du Conseil de Paris.',
                'https://fr.wikipedia.org/wiki/Jean-Pierre_Lecoq',
                [self::LR],
                [self::mandate(self::arrondissement(6), 'Maire', $start)],
            ),
            self::person(
                'Rachida',
                'Dati',
                'Maire du 7e arrondissement',
                'Rachida Dati, membre des Républicains, est maire du 7e arrondissement et coprésidente du groupe Paris Liberté au Conseil de Paris. Elle était candidate à la mairie de Paris en 2026.',
                'https://fr.wikipedia.org/wiki/Rachida_Dati',
                [self::LR],
                [self::mandate(self::arrondissement(7), 'Maire', $start)],
            ),
            self::person(
                'Catherine',
                'Lécuyer',
                'Maire du 8e arrondissement',
                'Catherine Lécuyer, membre des Républicains, est maire du 8e arrondissement depuis le 29 mars 2026. Elle siège au groupe Paris Liberté du Conseil de Paris.',
                null,
                [self::LR],
                [self::mandate(self::arrondissement(8), 'Maire', $start)],
            ),
            self::person(
                'Delphine',
                'Bürkli',
                'Maire du 9e arrondissement',
                'Delphine Bürkli, membre d\'Horizons, est maire du 9e arrondissement et conseillère régionale d\'Île-de-France. Elle siège au groupe Paris au centre du Conseil de Paris.',
                'https://fr.wikipedia.org/wiki/Delphine_Bürkli',
                [self::HORIZONS],
                [self::mandate(self::arrondissement(9), 'Maire', $start)],
            ),
            self::person(
                'Alexandra',
                'Cordebard',
                'Maire du 10e arrondissement',
                'Alexandra Cordebard, membre du Parti socialiste, est maire du 10e arrondissement. Elle siège au groupe socialiste et divers gauche du Conseil de Paris.',
                'https://fr.wikipedia.org/wiki/Alexandra_Cordebard',
                [self::PS],
                [self::mandate(self::arrondissement(10), 'Maire', $start)],
            ),
            self::person(
                'David',
                'Belliard',
                'Maire du 11e arrondissement',
                'David Belliard, membre des Écologistes, est conseiller de Paris depuis 2014. Adjoint d\'Anne Hidalgo aux transports et aux mobilités de 2020 à 2026, il est maire du 11e arrondissement depuis le 29 mars 2026.',
                'https://fr.wikipedia.org/wiki/David_Belliard',
                [self::ECO],
                [
                    self::mandate(self::CITY, 'Adjoint à la transformation de l\'espace public, aux transports, aux mobilités, au code de la rue et à la voirie', '2020-07-03', '2026-03-29'),
                    self::mandate(self::arrondissement(11), 'Maire', $start),
                ],
            ),
            self::person(
                'Lucie',
                'Castets',
                'Maire du 12e arrondissement',
                'Lucie Castets, candidate à Matignon du Nouveau Front populaire en 2024, est maire du 12e arrondissement depuis le 29 mars 2026. Elle siège comme divers gauche au groupe écologiste et social du Conseil de Paris.',
                'https://fr.wikipedia.org/wiki/Lucie_Castets',
                [],
                [self::mandate(self::arrondissement(12), 'Maire', $start)],
            ),
            self::person(
                'Jérôme',
                'Coumet',
                'Maire du 13e arrondissement',
                'Jérôme Coumet est maire du 13e arrondissement et coprésident du groupe socialiste et divers gauche au Conseil de Paris. Il y siège comme divers gauche.',
                'https://fr.wikipedia.org/wiki/Jérôme_Coumet',
                [],
                [self::mandate(self::arrondissement(13), 'Maire', $start)],
            ),
            self::person(
                'Carine',
                'Petit',
                'Maire du 14e arrondissement',
                'Carine Petit est maire du 14e arrondissement depuis 2014, d\'abord au Parti socialiste, puis à Génération.s et aujourd\'hui chez Les Écologistes. Elle siège au groupe écologiste et social du Conseil de Paris.',
                'https://fr.wikipedia.org/wiki/Carine_Petit',
                [self::ECO],
                [self::mandate(self::arrondissement(14), 'Maire', $start)],
            ),
            self::person(
                'Philippe',
                'Goujon',
                'Maire du 15e arrondissement',
                'Philippe Goujon, membre des Républicains, est maire du 15e arrondissement. Il siège au groupe Paris Liberté du Conseil de Paris.',
                'https://fr.wikipedia.org/wiki/Philippe_Goujon',
                [self::LR],
                [self::mandate(self::arrondissement(15), 'Maire', $start)],
            ),
            self::person(
                'Jérémy',
                'Redler',
                'Maire du 16e arrondissement',
                'Jérémy Redler, membre des Républicains, est maire du 16e arrondissement depuis le 7 novembre 2023, date à laquelle il succède à Francis Szpiner. Il est réélu au premier tour des municipales de 2026.',
                'https://fr.wikipedia.org/wiki/Jérémy_Redler',
                [self::LR],
                [self::mandate(self::arrondissement(16), 'Maire', '2023-11-07')],
            ),
            self::person(
                'Geoffroy',
                'Boulard',
                'Maire du 17e arrondissement',
                'Geoffroy Boulard, membre des Républicains, est maire du 17e arrondissement. Il siège au groupe Paris Liberté du Conseil de Paris.',
                'https://fr.wikipedia.org/wiki/Geoffroy_Boulard',
                [self::LR],
                [self::mandate(self::arrondissement(17), 'Maire', $start)],
            ),
            self::person(
                'Éric',
                'Lejoindre',
                'Maire du 18e arrondissement',
                'Éric Lejoindre, membre du Parti socialiste, est maire du 18e arrondissement. Il siège au groupe socialiste et divers gauche du Conseil de Paris.',
                'https://fr.wikipedia.org/wiki/Éric_Lejoindre',
                [self::PS],
                [self::mandate(self::arrondissement(18), 'Maire', $start)],
            ),
            self::person(
                'François',
                'Dagnaud',
                'Maire du 19e arrondissement',
                'François Dagnaud, membre du Parti socialiste, est maire du 19e arrondissement. Il siège au groupe socialiste et divers gauche du Conseil de Paris.',
                'https://fr.wikipedia.org/wiki/François_Dagnaud',
                [self::PS],
                [self::mandate(self::arrondissement(19), 'Maire', $start)],
            ),
            self::person(
                'Éric',
                'Pliez',
                'Maire du 20e arrondissement',
                'Éric Pliez, membre de Place publique, est maire du 20e arrondissement. Il siège au groupe socialiste et divers gauche du Conseil de Paris.',
                'https://fr.wikipedia.org/wiki/Éric_Pliez',
                [self::PP],
                [self::mandate(self::arrondissement(20), 'Maire', $start)],
            ),
        ];
    }

    /**
     * @return list<array{firstname: string, lastname: string, shortDescription: string, bio: string, wikiUrl: ?string, parties: list<string>, mandates: list<array{legalEntity: string, position: string, start: string, end: ?string}>}>
     */
    private static function groupPresidents(): array
    {
        $start = '2026-03-29';

        return [
            self::person(
                'Ian',
                'Brossat',
                'Coprésident du groupe communiste au Conseil de Paris',
                'Ian Brossat, membre du Parti communiste français, est conseiller de Paris depuis 2008 et sénateur de Paris depuis septembre 2023. Adjoint d\'Anne Hidalgo au logement de 2014 à 2023, il copréside le groupe communiste au Conseil de Paris.',
                'https://fr.wikipedia.org/wiki/Ian_Brossat',
                [self::PCF],
                [
                    self::mandate(self::CITY, 'Adjoint au logement, à l\'hébergement d\'urgence et à la protection des réfugiés', '2014-04-05', '2023-09-24'),
                    self::mandate(self::CITY, 'Conseiller de Paris, coprésident du groupe Communiste de Paris', $start),
                ],
            ),
            self::person(
                'Maud',
                'Gatel',
                'Présidente du groupe Paris au centre',
                'Maud Gatel, membre du MoDem, préside le groupe Paris au centre au Conseil de Paris depuis le 29 mars 2026. Elle est aussi conseillère de la Métropole du Grand Paris.',
                'https://fr.wikipedia.org/wiki/Maud_Gatel',
                [self::MODEM],
                [self::mandate(self::CITY, 'Conseillère de Paris, présidente du groupe Paris au centre', $start)],
            ),
            self::person(
                'Sophia',
                'Chikirou',
                'Coprésidente du groupe Nouveau Paris populaire',
                'Sophia Chikirou, membre de La France insoumise, est députée de Paris depuis 2022. Elle copréside le groupe Nouveau Paris populaire au Conseil de Paris depuis le 29 mars 2026.',
                'https://fr.wikipedia.org/wiki/Sophia_Chikirou',
                [self::LFI],
                [self::mandate(self::CITY, 'Conseillère de Paris, coprésidente du groupe Nouveau Paris populaire', $start)],
            ),
            self::person(
                'Émile',
                'Meunier',
                'Coprésident du groupe Nouveau Paris populaire',
                'Émile Meunier copréside le groupe Nouveau Paris populaire au Conseil de Paris depuis le 29 mars 2026. Il y siège avec l\'étiquette Les Verts populaires.',
                null,
                [],
                [self::mandate(self::CITY, 'Conseiller de Paris, coprésident du groupe Nouveau Paris populaire', $start)],
            ),
        ];
    }
}
