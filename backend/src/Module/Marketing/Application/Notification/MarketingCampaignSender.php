<?php

declare(strict_types=1);

namespace App\Module\Marketing\Application\Notification;

use App\Module\Marketing\Application\Provider\MarketingAudienceProvider;
use App\Module\Marketing\Application\Provider\MarketingRecipientContextProvider;
use App\Module\Marketing\Application\Workflow\MarketingTemplateRenderer;
use App\Module\Marketing\Domain\Entity\EmailCampaign;
use App\Module\Marketing\Domain\Entity\EmailTemplate;
use App\Module\Notification\Application\Notification\UserCommunicationNotifier;
use App\Shared\Infrastructure\Doctrine\DoctrineUnitOfWork;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

final readonly class MarketingCampaignSender
{
    public function __construct(
        private MarketingAudienceProvider $audiences,
        private MarketingRecipientContextProvider $contexts,
        private MarketingTemplateRenderer $renderer,
        private MailerInterface $mailer,
        private UserCommunicationNotifier $userNotifications,
        private DoctrineUnitOfWork $persistence,
        private string $mailerFrom,
    ) {
    }

    /** @param array<string, mixed> $criteria */
    public function send(
        string $name,
        string $segmentKey,
        array $criteria,
        string $subject,
        string $htmlBody,
        ?string $textBody,
        ?EmailTemplate $template,
        ?string $createdByEmail,
    ): EmailCampaign {
        $users = $this->audiences->resolveRecipients($segmentKey, $criteria);

        foreach ($users as $user) {
            if (!$this->userNotifications->shouldSendNewsEmail($user)) {
                continue;
            }

            $context = $this->contexts->provide($user);
            $renderedSubject = $this->renderer->render($subject, $context, false);
            $renderedHtml = $this->renderer->render($htmlBody, $context, true);
            $renderedText = $this->renderer->render($textBody ?: strip_tags($htmlBody), $context, false);
            $email = (new Email())
                ->from(new Address($this->mailerFrom, 'Hociatec'))
                ->to(new Address($user->getEmail(), $user->getFullName()))
                ->subject($renderedSubject)
                ->html($renderedHtml)
                ->text($renderedText);

            $this->mailer->send($email);
        }

        $campaign = new EmailCampaign(
            $name,
            $segmentKey,
            $criteria,
            $subject,
            $htmlBody,
            $textBody,
            count($users),
            $createdByEmail,
            $template,
        );
        $this->persistence->persist($campaign);
        $this->persistence->commit();

        return $campaign;
    }
}
