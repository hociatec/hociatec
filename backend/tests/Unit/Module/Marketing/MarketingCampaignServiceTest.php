<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Marketing;

use App\Module\Marketing\Domain\Entity\EmailCampaign;
use App\Module\Marketing\Domain\Entity\EmailTemplate;
use App\Module\Notification\Application\Workflow\CommunicationPreferences;
use App\Module\Outbox\Domain\Entity\OutboxEvent;
use Symfony\Component\Mailer\MailerInterface;

final class MarketingCampaignServiceTest extends MarketingIntegrationTestCase
{
    public function testCampaignServiceDelegatesAudiencePreviewResolveAndSend(): void
    {
        $em = $this->entityManager();
        $newsUser = $this->user('news@example.com', [CommunicationPreferences::NEWS_EMAIL]);
        $silentUser = $this->user('silent@example.com', []);
        $em->persist($newsUser);
        $em->persist($silentUser);
        $em->flush();

        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::never())->method('send');

        $service = $this->campaignService($em, $mailer);

        self::assertArrayHasKey('all_verified_users', $service->getSegmentDefinitions());
        self::assertSame(2, $service->previewAudience('all_verified_users', [])['count']);
        self::assertCount(1, $service->resolveRecipients('all_verified_users', [], 1));

        $template = new EmailTemplate('News', 'news', 'marketing_news', 'Sujet', '<p>Body</p>');
        $em->persist($template);
        $em->flush();
        $campaign = $service->sendCampaign(
            'Campagne aout',
            'all_verified_users',
            [],
            'Bonjour {{first_name}}',
            '<p>{{email}}</p>',
            '{{app_frontend_url}}',
            $template,
            'admin@example.com',
        );

        self::assertInstanceOf(EmailCampaign::class, $campaign);
        self::assertSame(0, $campaign->getRecipientsCount());
        self::assertSame(0, $campaign->getPendingCount());
        self::assertSame(0, $campaign->getSkippedCount());
        self::assertSame('admin@example.com', $campaign->getCreatedByEmail());
        self::assertSame(1, $em->getRepository(OutboxEvent::class)->count(['type' => 'marketing.campaign.prepare_requested']));
    }
}
