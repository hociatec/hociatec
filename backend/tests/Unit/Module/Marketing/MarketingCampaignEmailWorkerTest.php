<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Marketing;

use App\Module\Marketing\Application\Message\MarketingCampaignRecipientEmailMessage;
use App\Module\Marketing\Application\Provider\MarketingRecipientContextProvider;
use App\Module\Marketing\Application\Workflow\MarketingTemplateRenderer;
use App\Module\Marketing\Domain\Entity\EmailCampaign;
use App\Module\Marketing\Domain\Entity\EmailCampaignContentSnapshot;
use App\Module\Marketing\Domain\Entity\EmailCampaignRecipient;
use App\Module\Marketing\Infrastructure\MessageHandler\MarketingCampaignEmailSender;
use App\Module\Marketing\Infrastructure\MessageHandler\SendMarketingCampaignRecipientEmailHandler;
use App\Module\Marketing\Infrastructure\Repository\DoctrineMarketingRecipientContextQuery;
use App\Module\Marketing\Infrastructure\Repository\EmailCampaignRecipientRepository;
use App\Module\Notification\Application\Workflow\CommunicationPreferences;
use App\Module\User\Infrastructure\Repository\UserRepository;
use App\Shared\Application\Mail\EmailSender;
use App\Shared\Infrastructure\Doctrine\DoctrineUnitOfWork;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mime\Email;

final class MarketingCampaignEmailWorkerTest extends MarketingIntegrationTestCase
{
    public function testMarketingRecipientWorkerMarksSentAndIgnoresReplay(): void
    {
        $em = $this->entityManager();
        $user = $this->user('news-worker@example.com', [CommunicationPreferences::NEWS_EMAIL]);
        $campaign = new EmailCampaign('Worker campaign', 'all_verified_users', [], new EmailCampaignContentSnapshot('Bonjour {{first_name}}', '<p>{{email}}</p>'), 0, 'admin@example.com');
        $recipient = EmailCampaignRecipient::pending($campaign, $user);
        $em->persist($user);
        $em->persist($campaign);
        $em->persist($recipient);
        $em->flush();

        $mailer = $this->createMock(EmailSender::class);
        $mailer->expects(self::once())
            ->method('send')
            ->with(self::callback(static fn (Email $email): bool => 'Bonjour Ada' === $email->getSubject()));

        $handler = new SendMarketingCampaignRecipientEmailHandler(
            new EmailCampaignRecipientRepository($this->registry($em)),
            new UserRepository($this->registry($em)),
            new MarketingCampaignEmailSender(
                new MarketingRecipientContextProvider(new DoctrineMarketingRecipientContextQuery($em), 'https://front.example.test'),
                new MarketingTemplateRenderer(),
                $mailer,
                'noreply@example.com',
            ),
            $this->notifier($em),
            new DoctrineUnitOfWork($em),
            $this->createMock(LoggerInterface::class),
        );

        $message = new MarketingCampaignRecipientEmailMessage((int) $campaign->getId(), (int) $user->getId());
        $handler($message);
        $handler($message);

        self::assertSame(EmailCampaignRecipient::STATUS_SENT, $recipient->getStatus());
        self::assertSame(0, $campaign->getPendingCount());
        self::assertSame(1, $campaign->getSentCount());
    }
}
