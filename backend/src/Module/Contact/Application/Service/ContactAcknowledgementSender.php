<?php

declare(strict_types=1);

namespace App\Module\Contact\Application\Service;

use App\Module\Contact\Application\DTO\ContactInput;
use App\Module\Marketing\Application\Service\EmailTemplateRenderer;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

final readonly class ContactAcknowledgementSender
{
    public function __construct(
        private EmailTemplateRenderer $templates,
        private MailerInterface $mailer,
        private string $mailerFrom,
    ) {
    }

    public function send(ContactInput $input): void
    {
        $content = $this->templates->renderScenario('contact_acknowledgement', [
            'contact_name' => $input->name,
            'contact_email' => $input->email,
            'contact_subject' => $input->subject,
            'contact_message' => $input->message,
        ], [
            'subject' => 'Merci de nous avoir contactés',
            'html' => '<p>Bonjour {{contact_name}},</p><p>Merci de nous avoir contactés. Nous avons bien reçu votre demande et allons la traiter rapidement.</p><p>Résumé de votre message :</p><blockquote style="border-left:4px solid #ddd;padding-left:8px;color:#444">{{contact_message}}</blockquote><p>Cet e-mail est automatique, merci de ne pas y répondre. Nous reviendrons vers vous dès que possible.</p>',
            'text' => "Bonjour {{contact_name}},\n\nMerci de nous avoir contactés. Nous avons bien reçu votre demande et allons la traiter rapidement.\n\nRésumé de votre message :\n{{contact_message}}\n\nCet e-mail est automatique, merci de ne pas y répondre.",
        ]);

        $email = (new Email())
            ->from(new Address($this->mailerFrom, 'Hociatec'))
            ->to(new Address($input->email, $input->name))
            ->subject($content['subject'])
            ->html($content['html'])
            ->text($content['text']);

        $this->mailer->send($email);
    }
}
