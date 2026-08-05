<?php

declare(strict_types=1);

namespace App\Module\Contact\Application\Workflow;

use App\Module\Contact\Application\DTO\ContactInput;
use App\Module\Contact\Application\Notification\ContactAcknowledgementSender;
use App\Module\Contact\Application\Notification\ContactNotificationSender;
use App\Shared\Application\Exception\MailDeliveryException;
use Psr\Log\LoggerInterface;

final readonly class ContactSubmissionService
{
    public function __construct(
        private ContactNotificationSender $notification,
        private ContactAcknowledgementSender $acknowledgement,
        private LoggerInterface $logger,
    ) {
    }

    public function submit(ContactInput $input): void
    {
        try {
            $this->notification->send($input);
        } catch (MailDeliveryException $exception) {
            throw $exception;
        } catch (\RuntimeException $exception) {
            throw MailDeliveryException::failed('contact_admin_notification', $exception);
        }

        try {
            $this->acknowledgement->send($input);
        } catch (\RuntimeException $exception) {
            $this->logger->warning('Contact acknowledgement could not be delivered.', [
                'exception' => $exception,
            ]);
        }
    }
}
