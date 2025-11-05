<?php

declare(strict_types=1);

namespace App\Module\Audit\Service;

use App\Module\Audit\Entity\AuditType;

/**
 * Provides default checklist templates per audit type.
 */
class AuditTemplateProvider
{
    /**
     * @return array<string, list<array{key: string, label: string}>> category => items
     */
    public function getTemplate(AuditType $type): array
    {
        return match ($type) {
            AuditType::ACCESSIBILITY => $this->accessibilityTemplate(),
            AuditType::PERFORMANCE => $this->performanceTemplate(),
            AuditType::SECURITY => $this->securityTemplate(),
            AuditType::UX => $this->uxTemplate(),
            AuditType::SEO => $this->seoTemplate(),
            AuditType::TECHNICAL => $this->technicalTemplate(),
        };
    }

    /** @return array<string, list<array{key: string, label: string}>> */
    private function accessibilityTemplate(): array
    {
        // Expanded with WCAG levels (A/AA/AAA) commonly aligned to RGAA topics
        return [
            'Structure' => [
                ['key' => 'landmarks',   'label' => 'Repères ARIA (header, main, nav, footer)', 'level' => 'A'],
                ['key' => 'headings',    'label' => 'Hiérarchie des titres cohérente (H1→H6)',    'level' => 'A'],
                ['key' => 'lang',        'label' => 'Attribut de langue (html lang) renseigné',   'level' => 'A'],
                ['key' => 'semantics',   'label' => 'Elements sémantiques appropriés',            'level' => 'A'],
            ],
            'Navigation' => [
                ['key' => 'keyboard',    'label' => 'Navigation clavier complète',                'level' => 'A'],
                ['key' => 'focus',       'label' => 'Focus visible et ordre logique',             'level' => 'AA'],
                ['key' => 'skiplinks',   'label' => 'Liens d’évitement (aller au contenu)',       'level' => 'A'],
                ['key' => 'bypass',      'label' => 'Moyens de contourner des blocs répétés',     'level' => 'A'],
            ],
            'Contenu' => [
                ['key' => 'alt',         'label' => 'Textes alternatifs images/icônes',          'level' => 'A'],
                ['key' => 'contrast',    'label' => 'Contraste suffisant (WCAG AA)',              'level' => 'AA'],
                ['key' => 'reflow',      'label' => 'Reflow sans perte de contenu (zoom)',        'level' => 'AA'],
                ['key' => 'media',       'label' => 'Sous-titres/transcriptions pour médias',     'level' => 'A'],
                ['key' => 'audioctrl',   'label' => 'Contrôle des sons en lecture auto',          'level' => 'A'],
            ],
            'Formulaires' => [
                ['key' => 'labels',      'label' => 'Labels associés et instructions claires',    'level' => 'A'],
                ['key' => 'errors',      'label' => 'Messages d’erreur accessibles',              'level' => 'A'],
                ['key' => 'name-role',   'label' => 'Name/Role/Value pour composants',            'level' => 'A'],
                ['key' => 'autocomplete','label' => 'Attributs autocomplete pertinents',          'level' => 'AA'],
            ],
            'Interactions' => [
                ['key' => 'pointer-gestures', 'label' => 'Gestes alternatifs pour interactions',  'level' => 'A'],
                ['key' => 'motion-actuation','label' => 'Pas d’obligation d’inclinaison/mouvement','level' => 'A'],
                ['key' => 'target-size',     'label' => 'Cibles tactiles de taille suffisante',   'level' => 'AAA'],
            ],
        ];
    }

    /** @return array<string, list<array{key: string, label: string}>> */
    private function performanceTemplate(): array
    {
        return [
            'Chargement' => [
                ['key' => 'metrics', 'label' => 'Core Web Vitals (LCP, CLS, INP)'],
                ['key' => 'caching', 'label' => 'Cache HTTP et CDN configurés'],
                ['key' => 'assets', 'label' => 'Minification/concaténation des ressources'],
            ],
            'Ressources' => [
                ['key' => 'images', 'label' => 'Images optimisées (formats modernes, lazyload)'],
                ['key' => 'third_party', 'label' => 'Scripts tiers contrôlés et différés'],
            ],
        ];
    }

    /** @return array<string, list<array{key: string, label: string}>> */
    private function securityTemplate(): array
    {
        return [
            'Transport' => [
                ['key' => 'tls', 'label' => 'TLS activé (HSTS, redirections HTTPS)'],
                ['key' => 'headers', 'label' => 'En-têtes de sécurité (CSP, X-Frame-Options, etc.)'],
            ],
            'Application' => [
                ['key' => 'auth', 'label' => 'Auth/RBAC conformes, sessions sécurisées'],
                ['key' => 'vulns', 'label' => 'Absence de vulnérabilités connues (dépendances)'],
            ],
        ];
    }

    /** @return array<string, list<array{key: string, label: string}>> */
    private function uxTemplate(): array
    {
        return [
            'Lisibilité' => [
                ['key' => 'typography', 'label' => 'Taille et interlignage lisibles'],
                ['key' => 'contrast', 'label' => 'Contraste texte/fond confortable'],
            ],
            'Parcours' => [
                ['key' => 'navigation', 'label' => 'Navigation claire et cohérente'],
                ['key' => 'feedback', 'label' => 'Feedback explicite des actions'],
            ],
        ];
    }

    /** @return array<string, list<array{key: string, label: string}>> */
    private function seoTemplate(): array
    {
        return [
            'Indexation' => [
                ['key' => 'robots', 'label' => 'robots.txt et balises meta robots correctes'],
                ['key' => 'sitemap', 'label' => 'Sitemap soumis et cohérent'],
            ],
            'Contenu' => [
                ['key' => 'titles', 'label' => 'Titres et meta descriptions uniques'],
                ['key' => 'schema', 'label' => 'Données structurées (Schema.org)'],
            ],
        ];
    }

    /** @return array<string, list<array{key: string, label: string}>> */
    private function technicalTemplate(): array
    {
        return [
            'Architecture' => [
                ['key' => 'versioning', 'label' => 'Dépendances et mises à jour à jour'],
                ['key' => 'logging', 'label' => 'Journalisation et monitoring'],
            ],
            'Qualité' => [
                ['key' => 'tests', 'label' => 'Tests, CI/CD et couverture'],
                ['key' => 'lint', 'label' => 'Linting/formatting configurés'],
            ],
        ];
    }
}
