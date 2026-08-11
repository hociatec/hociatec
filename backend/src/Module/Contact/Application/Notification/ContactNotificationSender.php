<?php

declare(strict_types=1);

namespace App\Module\Contact\Application\Notification;

use App\Module\Contact\Application\DTO\ContactInput;
use App\Module\Marketing\Application\Notification\EmailTemplateRenderer;
use App\Shared\Application\Mail\EmailHeaderSanitizer;
use App\Shared\Application\Mail\EmailSender;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

final readonly class ContactNotificationSender
{
    public function __construct(
        private EmailTemplateRenderer $templates,
        private EmailSender $mailer,
        private string $mailerFrom,
        private string $contactRecipient,
    ) {
    }

    public function send(ContactInput $input): void
    {
        $content = $this->templates->renderScenario('contact_admin_notification', [
            'contact_name' => $input->name,
            'contact_email' => $input->email,
            'contact_subject' => $input->subject,
            'contact_message' => $input->message,
        ], [
            'subject' => '[Contact] {{contact_subject}}',
            'html' => '<p><strong>Nom :</strong> {{contact_name}}</p><p><strong>E-mail :</strong> {{contact_email}}</p><p><strong>Sujet :</strong> {{contact_subject}}</p><p><strong>Message :</strong></p><p>{{contact_message}}</p>',
            'text' => "Nom : {{contact_name}}\nE-mail : {{contact_email}}\nSujet : {{contact_subject}}\n\n{{contact_message}}",
        ]);

        $email = (new Email())
            ->from(new Address($this->mailerFrom, 'Hociatec'))
            ->to(new Address($this->contactRecipient, 'Hociatec Contact'))
            ->replyTo(new Address($input->email, EmailHeaderSanitizer::displayName($input->name)))
            ->subject(EmailHeaderSanitizer::subject($content['subject']))
            ->html($content['html'])
            ->text($content['text']);

        $this->mailer->send($email);
    }
}
