<?php

declare(strict_types=1);

namespace App\Module\Marketing\Infrastructure\MessageHandler;

use App\Module\Marketing\Application\Provider\MarketingRecipientContextProvider;
use App\Module\Marketing\Application\Workflow\MarketingTemplateRenderer;
use App\Module\Marketing\Domain\Entity\EmailCampaign;
use App\Module\User\Domain\Entity\User;
use App\Shared\Application\Mail\EmailSender;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

final readonly class MarketingCampaignEmailSender
{
    public function __construct(
        private MarketingRecipientContextProvider $contexts,
        private MarketingTemplateRenderer $renderer,
        private EmailSender $mailer,
        private string $mailerFrom,
    ) {
    }

    public function send(EmailCampaign $campaign, User $user): void
    {
        $context = $this->contexts->provide($user);
        $email = (new Email())
            ->from(new Address($this->mailerFrom, 'Hociatec'))
            ->to(new Address($user->getEmail(), $user->getFullName()))
            ->subject($this->renderer->render($campaign->getSubjectSnapshot(), $context, false))
            ->html($this->renderer->render($campaign->getHtmlSnapshot(), $context, true))
            ->text($this->renderer->render($campaign->getTextSnapshot() ?: strip_tags($campaign->getHtmlSnapshot()), $context, false));

        $this->mailer->send($email);
    }
}
