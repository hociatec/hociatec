<?php

declare(strict_types=1);

namespace App\Module\Support\Application\Workflow;

use App\Module\Order\Domain\Entity\Order;
use App\Module\Support\Application\DTO\SupportCreateData;
use App\Module\Support\Application\DTO\SupportReplyData;
use App\Module\Support\Application\DTO\SupportUpdateData;
use App\Module\Support\Application\Port\SupportCustomerMessengerPort;
use App\Module\Support\Domain\Entity\SupportRequest;
use App\Module\Support\Domain\Enum\SupportStatus;
use App\Module\User\Domain\Entity\User;
use App\Shared\Application\UnitOfWork;

final readonly class SupportRequestService
{
    public function __construct(
        private UnitOfWork $persistence,
        private SupportCustomerMessengerPort $messenger,
        private SupportAttachmentNormalizer $attachments,
        private SupportRequestTimelineRecorder $timeline,
    ) {
    }

    /** @param list<array<string, mixed>> $attachments */
    public function create(User $customer, SupportCreateData $data, ?Order $order = null, array $attachments = []): SupportRequest
    {
        $support = new SupportRequest($customer, $data->subject);
        $support
            ->setReason($data->reason)
            ->setMessage($data->message)
            ->setInternalNotes($data->internalNotes)
            ->setAttachments($this->attachments()->normalize($attachments));

        $order instanceof Order
            ? $support->setOrderId($order->getId(), $order->getNumber())
            : $support->setOrderId(null);

        $initialAttachments = $this->attachments()->normalize($support->getAttachments());
        $this->timeline()->recordCreation($support, $customer, $initialAttachments);

        $this->persistence->persist($support);
        $this->persistence->flush();

        return $support;
    }

    public function update(SupportRequest $support, SupportUpdateData $data): SupportRequest
    {
        $previousStatus = $support->getStatus();
        $previousInternalNotes = $support->getInternalNotes();
        $previousSubject = $support->getSubject();

        if (null !== $data->status && null === SupportStatus::tryFrom($data->status)) {
            throw new \InvalidArgumentException('Statut de support invalide.');
        }
        if (null !== $data->status) {
            $support->setStatus($data->status);
        }
        if (null !== $data->internalNotes) {
            $support->setInternalNotes($data->internalNotes);
        }
        if (null !== $data->subject) {
            $support->setSubject($data->subject);
        }

        $this->timeline()->recordAdminUpdate($support, $data, $previousStatus, $previousInternalNotes, $previousSubject);

        $this->persistence->flush();

        return $support;
    }

    public function reply(SupportRequest $support, SupportReplyData $data): SupportRequest
    {
        $previousStatus = $support->getStatus();
        $subject = trim($data->subject ?? ('Réponse à votre demande SAV #'.$support->getId()));
        $message = trim($data->message);
        if ('' === $message) {
            throw new \InvalidArgumentException('Le message de réponse est obligatoire.');
        }

        $this->messenger->send($support->getCustomer(), $subject, $message);
        $now = new \DateTimeImmutable();
        $note = trim(sprintf(
            "%s\n[%s] Réponse envoyée au client : %s",
            (string) $support->getInternalNotes(),
            $now->format('d/m/Y H:i'),
            $subject,
        ));
        $support
            ->setInternalNotes($note)
            ->setStatus($data->status ?? SupportRequest::STATUS_WAITING_CUSTOMER);

        $this->timeline()->recordAdminReply($support, $subject, $message, $previousStatus, $now);
        $this->persistence->flush();

        return $support;
    }

    /** @param list<array<string, mixed>> $attachments */
    public function replyFromCustomer(SupportRequest $support, User $customer, string $message, ?string $subject = null, array $attachments = []): SupportRequest
    {
        $message = trim($message);
        if ('' === $message) {
            throw new \InvalidArgumentException('Le message de réponse est obligatoire.');
        }

        $subject = '' !== ($subject = trim($subject ?? '')) ? $subject : ('Complément à votre demande SAV #'.$support->getId());
        $now = new \DateTimeImmutable();
        $previousStatus = $support->getStatus();
        $normalizedAttachments = $this->attachments()->normalize($attachments);

        if ([] !== $normalizedAttachments) {
            $support->setAttachments(array_merge($support->getAttachments(), $normalizedAttachments));
        }

        if (SupportRequest::STATUS_IN_PROGRESS !== $previousStatus) {
            $support->setStatus(SupportRequest::STATUS_IN_PROGRESS);
        }
        $this->timeline()->recordCustomerReply($support, $customer, $subject, $message, $previousStatus, $now, $normalizedAttachments);

        $this->persistence->flush();

        return $support;
    }

    private function timeline(): SupportRequestTimelineRecorder
    {
        return $this->timeline;
    }

    private function attachments(): SupportAttachmentNormalizer
    {
        return $this->attachments;
    }
}
