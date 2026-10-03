<?php

namespace App\DataFixtures;

final class ParisThemesCatalog
{
    /**
     * @return list<array{name: string, color: string, shortDescription: string}>
     */
    public static function themes(): array
    {
        return [
            [
                'name' => 'Finances & budget',
                'color' => '#2F5233',
                'shortDescription' => 'Dépenses, marchés publics, subventions et arbitrages budgétaires.',
            ],
            [
                'name' => 'Urbanisme & logement',
                'color' => '#5C4B3A',
                'shortDescription' => 'Rénovation urbaine, logement social, permis et aménagement du territoire.',
            ],
            [
                'name' => 'Transports & mobilité',
                'color' => '#1F4E79',
                'shortDescription' => 'Vélo, stationnement, chantiers viabilisation et déplacements parisiens.',
            ],
            [
                'name' => 'Éducation & jeunesse',
                'color' => '#7A1F2B',
                'shortDescription' => 'Écoles, périscolaire, cantines et politiques de la petite enfance.',
            ],
            [
                'name' => 'Sécurité & ordre public',
                'color' => '#3F3A36',
                'shortDescription' => 'Police municipale, manifestations, risques et faits urbains sensibles.',
            ],
            [
                'name' => 'Environnement & propreté',
                'color' => '#4A6741',
                'shortDescription' => 'Déchets, qualité de l’air, espaces verts et adaptation climatique.',
            ],
            [
                'name' => 'Culture & sport',
                'color' => '#6B3FA0',
                'shortDescription' => 'Équipements, événements, patrimoine et grands rendez-vous sportifs.',
            ],
            [
                'name' => 'Social & solidarité',
                'color' => '#B85C38',
                'shortDescription' => 'Hébergement d’urgence, précarité et politiques d’accueil.',
            ],
            [
                'name' => 'Gouvernance & éthique',
                'color' => '#8C2F2F',
                'shortDescription' => 'Transparence, conflits d’intérêts, voyages officiels et nominations.',
            ],
            [
                'name' => 'Services & numérique',
                'color' => '#4A6FA5',
                'shortDescription' => 'Mairies d’arrondissement, démarches en ligne et modernisation des services.',
            ],
        ];
    }
}
