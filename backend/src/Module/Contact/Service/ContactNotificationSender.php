<?php

declare(strict_types=1);

namespace App\Module\Contact\Service;

use App\Module\Contact\DTO\ContactInput;
use App\Module\Marketing\Service\EmailTemplateRenderer;
use App\Shared\Mail\DualTransportMailer;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

final readonly class ContactNotificationSender
{
    public function __construct(
        private EmailTemplateRenderer $templates,
        private DualTransportMailer $mailer,
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
            ->replyTo(new Address($input->email, $input->name))
            ->subject($content['subject'])
            ->html($content['html'])
            ->text($content['text']);

        $this->mailer->send(
            $this->contactRecipient,
            $content['subject'],
            $content['text'],
            $email,
            'contact_admin_notification',
            $input->email,
        );
    }
}
