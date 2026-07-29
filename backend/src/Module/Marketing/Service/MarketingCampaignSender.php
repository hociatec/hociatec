<?php

declare(strict_types=1);

namespace App\Module\Marketing\Service;

use App\Module\Marketing\Entity\EmailCampaign;
use App\Module\Marketing\Entity\EmailTemplate;
use App\Module\Notification\Service\UserCommunicationNotifier;
use App\Shared\Persistence\DoctrinePersistence;
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
        private DoctrinePersistence $persistence,
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
        $this->persistence->flush();

        return $campaign;
    }
}
