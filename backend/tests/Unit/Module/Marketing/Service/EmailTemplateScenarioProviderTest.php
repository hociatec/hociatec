<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Marketing\Service;

use App\Module\Marketing\Application\Service\EmailTemplateScenarioProvider;
use PHPUnit\Framework\TestCase;

final class EmailTemplateScenarioProviderTest extends TestCase
{
    public function testScenarioProviderExposesCampaignTransactionalAndMergedDefinitions(): void
    {
        $provider = new EmailTemplateScenarioProvider();

        $campaigns = $provider->getCampaignScenarioDefinitions();
        $transactional = $provider->getTransactionalTemplateScenarioDefinitions();
        $merged = $provider->getTemplateScenarioDefinitions();

        self::assertCount(12, $campaigns);
        self::assertCount(13, $transactional);
        self::assertCount(25, $merged);

        self::assertSame('campaign', $campaigns['all_verified_users']['type']);
        self::assertSame([], $campaigns['all_verified_users']['defaults']);
        self::assertSame(30, $campaigns['recent_verified_users']['defaults']['registeredDays']);
        self::assertSame(3, $campaigns['loyal_customers']['defaults']['minimumOrders']);
        self::assertSame(50000, $campaigns['high_value_customers']['defaults']['minimumTotalCents']);
        self::assertSame(2, $campaigns['customers_with_pending_reviews']['defaults']['minimumPendingReviews']);
        self::assertSame(90, $campaigns['inactive_customers']['defaults']['inactiveDays']);

        self::assertSame('transactional', $transactional['order_created']['type']);
        self::assertSame([], $transactional['quote_created']['defaults']);
        self::assertStringContainsString('facture PDF/XML', $transactional['order_invoice_issued']['description']);
        self::assertStringContainsString('partagée par e-mail', $transactional['product_share']['description']);
        self::assertStringContainsString('reprise change de statut', $transactional['trade_in_status_changed']['description']);

        self::assertSame($campaigns['recent_customers'], $merged['recent_customers']);
        self::assertSame($transactional['password_reset'], $merged['password_reset']);
    }
}
