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
                ['key' => 'code_split', 'label' => 'Code splitting et lazy loading configurés'],
                ['key' => 'preload', 'label' => 'Préchargement/préconnexion (preload, preconnect) pertinents'],
            ],
            'Ressources' => [
                ['key' => 'images', 'label' => 'Images optimisées (formats modernes, lazyload)'],
                ['key' => 'third_party', 'label' => 'Scripts tiers contrôlés et différés'],
                ['key' => 'fonts', 'label' => 'Polices optimisées (display swap, subset, self-hosted)'],
            ],
            'Réseau' => [
                ['key' => 'http2', 'label' => 'HTTP/2 ou HTTP/3 activé'],
                ['key' => 'compression', 'label' => 'Compression (Gzip/Brotli) activée'],
                ['key' => 'server_timing', 'label' => 'Server-Timing et budgets de performance suivis'],
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
                ['key' => 'input_validation', 'label' => 'Validation/échappement des entrées (XSS, injections)'],
                ['key' => 'csrf', 'label' => 'Protection CSRF sur actions sensibles'],
            ],
            'Secrets & Données' => [
                ['key' => 'secrets', 'label' => 'Secrets gérés (vault, env), pas en clair'],
                ['key' => 'backup', 'label' => 'Sauvegardes et restauration testées'],
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
                ['key' => 'empty_states', 'label' => 'Écrans vides et erreurs pédagogiques'],
            ],
            'Mobile' => [
                ['key' => 'responsive', 'label' => 'Responsive design et points de rupture adaptés'],
                ['key' => 'touch_targets', 'label' => 'Cibles tactiles confortables'],
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
                ['key' => 'canonical', 'label' => 'URLs canoniques et gestion des doublons'],
            ],
            'Contenu' => [
                ['key' => 'titles', 'label' => 'Titres et meta descriptions uniques'],
                ['key' => 'schema', 'label' => 'Données structurées (Schema.org)'],
                ['key' => 'headings', 'label' => 'Structure Hn cohérente et descriptive'],
            ],
            'International' => [
                ['key' => 'hreflang', 'label' => 'Hreflang et ciblage géographique'],
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
                ['key' => 'error_reporting', 'label' => 'Reporting d’erreurs et suivi (Sentry, etc.)'],
            ],
            'Qualité' => [
                ['key' => 'tests', 'label' => 'Tests, CI/CD et couverture'],
                ['key' => 'lint', 'label' => 'Linting/formatting configurés'],
                ['key' => 'code_review', 'label' => 'Revue de code et conventions'],
            ],
            'Déploiement' => [
                ['key' => 'infra_as_code', 'label' => 'Infrastructure as Code (Terraform, Ansible, etc.)'],
                ['key' => 'backups', 'label' => 'Backups réguliers et procédures de restauration'],
                ['key' => 'observability', 'label' => 'Métriques, logs et traces corrélées'],
            ],
        ];
    }
}
