<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Audit\Service;

use App\Module\Audit\Entity\AuditType;
use App\Module\Audit\Service\AuditTemplateProvider;
use PHPUnit\Framework\TestCase;

final class AuditTemplateProviderTest extends TestCase
{
    public function testProviderReturnsExpectedTemplatesForEveryAuditType(): void
    {
        $provider = new AuditTemplateProvider();

        $accessibility = $provider->getTemplate(AuditType::ACCESSIBILITY);
        self::assertArrayHasKey('Structure', $accessibility);
        self::assertSame('A', $accessibility['Structure'][0]['level']);
        self::assertSame('keyboard', $accessibility['Navigation'][0]['key']);
        self::assertSame('AAA', $accessibility['Interactions'][2]['level']);

        $performance = $provider->getTemplate(AuditType::PERFORMANCE);
        self::assertSame('metrics', $performance['Chargement'][0]['key']);
        self::assertSame('fonts', $performance['Ressources'][2]['key']);
        self::assertSame('server_timing', $performance['Réseau'][2]['key']);

        $security = $provider->getTemplate(AuditType::SECURITY);
        self::assertSame('tls', $security['Transport'][0]['key']);
        self::assertSame('csrf', $security['Application'][3]['key']);
        self::assertSame('backup', $security['Secrets & Données'][1]['key']);

        $ux = $provider->getTemplate(AuditType::UX);
        self::assertSame('typography', $ux['Lisibilité'][0]['key']);
        self::assertSame('empty_states', $ux['Parcours'][2]['key']);
        self::assertSame('touch_targets', $ux['Mobile'][1]['key']);

        $seo = $provider->getTemplate(AuditType::SEO);
        self::assertSame('robots', $seo['Indexation'][0]['key']);
        self::assertSame('schema', $seo['Contenu'][1]['key']);
        self::assertSame('hreflang', $seo['International'][0]['key']);

        $technical = $provider->getTemplate(AuditType::TECHNICAL);
        self::assertSame('versioning', $technical['Architecture'][0]['key']);
        self::assertSame('tests', $technical['Qualité'][0]['key']);
        self::assertSame('observability', $technical['Déploiement'][2]['key']);
    }
}
