<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Marketing;

use App\Module\Marketing\Application\Message\MarketingCampaignRecipientEmailMessage;
use App\Module\Marketing\Application\Outbox\DispatchMarketingCampaignRecipientEmailHandler;
use App\Module\Marketing\Application\Outbox\PrepareMarketingCampaignHandler;
use App\Module\Marketing\Application\Provider\EmailTemplateScenarioProvider;
use App\Module\Marketing\Application\Provider\MarketingAudienceProvider;
use App\Module\Marketing\Domain\Entity\EmailCampaign;
use App\Module\Marketing\Domain\Entity\EmailCampaignRecipient;
use App\Module\Marketing\Infrastructure\Repository\DoctrineMarketingAudienceQuery;
use App\Module\Marketing\Infrastructure\Repository\EmailCampaignRecipientRepository;
use App\Module\Marketing\Infrastructure\Repository\EmailCampaignRepository;
use App\Module\Notification\Application\Workflow\CommunicationPreferences;
use App\Module\Outbox\Application\Outbox;
use App\Module\Outbox\Domain\Entity\OutboxEvent;
use App\Shared\Application\Messaging\AsyncMessageDispatcher;
use App\Shared\Infrastructure\Doctrine\DoctrineUnitOfWork;

final class MarketingCampaignOutboxHandlersTest extends MarketingIntegrationTestCase
{
    public function testMarketingCampaignPreparationOutboxCreatesRecipientsAndEmailOutboxEventsByCursor(): void
    {
        $em = $this->entityManager();
        $optIn = $this->user('prep-news@example.com', [CommunicationPreferences::NEWS_EMAIL]);
        $silent = $this->user('prep-silent@example.com', []);
        $campaign = new EmailCampaign('Prepared campaign', 'all_verified_users', [], 'Bonjour', '<p>Body</p>', null, 0, 'admin@example.com');
        $em->persist($optIn);
        $em->persist($silent);
        $em->persist($campaign);
        $em->flush();

        $persistence = new DoctrineUnitOfWork($em);
        $handler = new PrepareMarketingCampaignHandler(
            new EmailCampaignRepository($this->registry($em)),
            new MarketingAudienceProvider(new DoctrineMarketingAudienceQuery($em), new EmailTemplateScenarioProvider()),
            $this->notifier($em),
            new EmailCampaignRecipientRepository($this->registry($em)),
            new Outbox($persistence),
            $persistence,
        );

        $handler->handle(new OutboxEvent(
            PrepareMarketingCampaignHandler::prepareKey((int) $campaign->getId()),
            PrepareMarketingCampaignHandler::TYPE,
            ['campaignId' => (int) $campaign->getId(), 'lastUserId' => 0],
        ));
        $em->flush();

        self::assertSame(1, $campaign->getRecipientsCount());
        self::assertSame(1, $campaign->getPendingCount());
        self::assertSame(1, $campaign->getSkippedCount());
        self::assertSame(2, $em->getRepository(EmailCampaignRecipient::class)->count([]));
        self::assertSame(1, $em->getRepository(OutboxEvent::class)->count(['type' => DispatchMarketingCampaignRecipientEmailHandler::TYPE]));
    }

    public function testMarketingRecipientEmailOutboxPublishesMessengerMessage(): void
    {
        $messageBus = $this->createMock(AsyncMessageDispatcher::class);
        $messageBus->expects(self::once())
            ->method('dispatch')
            ->with(self::callback(static fn (object $message): bool => $message instanceof MarketingCampaignRecipientEmailMessage
                && 42 === $message->campaignId
                && 99 === $message->userId));

        (new DispatchMarketingCampaignRecipientEmailHandler($messageBus))->handle(new OutboxEvent(
            PrepareMarketingCampaignHandler::recipientEmailKey(42, 99),
            DispatchMarketingCampaignRecipientEmailHandler::TYPE,
            ['campaignId' => 42, 'userId' => 99],
        ));
    }
}
