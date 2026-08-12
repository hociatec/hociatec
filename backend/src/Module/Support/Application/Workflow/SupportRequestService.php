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
        private ?SupportCustomerMessengerPort $messenger = null,
    ) {
    }

    public function create(User $customer, SupportCreateData $data, ?Order $order = null, array $attachments = []): SupportRequest
    {
        $support = new SupportRequest($customer, $data->subject);
        $support
            ->setReason($data->reason)
            ->setMessage($data->message)
            ->setInternalNotes($data->internalNotes)
            ->setAttachments($this->normalizeAttachments($attachments));

        if ($order instanceof Order) {
            $support->setOrderId($order->getId(), $order->getNumber());
        } else {
            $support->setOrderId(null);
        }

        $initialAttachments = $this->normalizeAttachments($support->getAttachments());

        if (null !== $support->getMessage() && '' !== $support->getMessage()) {
            $support->appendTimelineEntry($this->buildTimelineEntry(
                'customer_message',
                'customer',
                'customer',
                $customer->getFullName(),
                $support->getSubject(),
                $support->getMessage(),
                null,
                $support->getCreatedAt(),
                $initialAttachments,
            ));
        }

        if (null !== $support->getInternalNotes() && '' !== $support->getInternalNotes()) {
            $support->appendTimelineEntry($this->buildTimelineEntry(
                'internal_note',
                'admin',
                'internal',
                'Équipe Hociatec',
                'Note interne',
                $support->getInternalNotes(),
                null,
                $support->getCreatedAt(),
            ));
        }

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

        if (null !== $data->subject && $support->getSubject() !== $previousSubject) {
            $support->appendTimelineEntry($this->buildTimelineEntry(
                'subject_change',
                'admin',
                'customer',
                'Équipe Hociatec',
                'Sujet mis à jour',
                sprintf(
                    'Ancien sujet : %s'."\n".'Nouveau sujet : %s',
                    $previousSubject,
                    $support->getSubject(),
                ),
                null,
            ));
        }

        if (null !== $data->internalNotes && $support->getInternalNotes() !== $previousInternalNotes) {
            $support->appendTimelineEntry($this->buildTimelineEntry(
                'internal_note',
                'admin',
                'internal',
                'Équipe Hociatec',
                'Note interne mise à jour',
                $support->getInternalNotes(),
                null,
            ));
        }

        if (null !== $data->status && $data->status !== $previousStatus) {
            $support->appendTimelineEntry($this->buildTimelineEntry(
                'status_change',
                'admin',
                'customer',
                'Équipe Hociatec',
                'Statut mis à jour',
                null,
                $data->status,
            ));
        }

        $this->persistence->flush();

        return $support;
    }

    public function reply(SupportRequest $support, SupportReplyData $data): SupportRequest
    {
        if (!$this->messenger instanceof SupportCustomerMessengerPort) {
            throw new \LogicException('Support customer messenger is not configured.');
        }

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
            ->appendTimelineEntry($this->buildTimelineEntry(
                'admin_reply',
                'admin',
                'customer',
                'Équipe Hociatec',
                $subject,
                $message,
                null,
                $now,
                [],
            ))
            ->setInternalNotes($note)
            ->setStatus($data->status ?? SupportRequest::STATUS_WAITING_CUSTOMER);

        if ($support->getStatus() !== $previousStatus) {
            $support->appendTimelineEntry($this->buildTimelineEntry(
                'status_change',
                'admin',
                'customer',
                'Équipe Hociatec',
                'Statut mis à jour',
                null,
                $support->getStatus(),
                $now,
            ));
        }
        $this->persistence->flush();

        return $support;
    }

    public function replyFromCustomer(SupportRequest $support, User $customer, string $message, ?string $subject = null, array $attachments = []): SupportRequest
    {
        $message = trim($message);
        if ('' === $message) {
            throw new \InvalidArgumentException('Le message de réponse est obligatoire.');
        }

        $subject = trim($subject ?? '');
        $subject = '' !== $subject ? $subject : ('Complément à votre demande SAV #'.$support->getId());
        $now = new \DateTimeImmutable();
        $previousStatus = $support->getStatus();
        $normalizedAttachments = $this->normalizeAttachments($attachments);

        if ([] !== $normalizedAttachments) {
            $support->setAttachments(array_merge($support->getAttachments(), $normalizedAttachments));
        }

        $support->appendTimelineEntry($this->buildTimelineEntry(
            'customer_reply',
            'customer',
            'customer',
            $customer->getFullName(),
            $subject,
            $message,
            null,
            $now,
            $normalizedAttachments,
        ));

        if (SupportRequest::STATUS_IN_PROGRESS !== $previousStatus) {
            $support->setStatus(SupportRequest::STATUS_IN_PROGRESS);
            $support->appendTimelineEntry($this->buildTimelineEntry(
                'status_change',
                'customer',
                'customer',
                $customer->getFullName(),
                'Statut mis à jour',
                null,
                SupportRequest::STATUS_IN_PROGRESS,
                $now,
            ));
        }

        $this->persistence->flush();

        return $support;
    }

    private function buildTimelineEntry(
        string $type,
        string $actor,
        string $visibility,
        string $authorLabel,
        ?string $subject,
        ?string $message,
        ?string $status,
        ?\DateTimeImmutable $createdAt = null,
        array $attachments = [],
    ): array {
        return [
            'id' => bin2hex(random_bytes(8)),
            'type' => $type,
            'actor' => $actor,
            'visibility' => $visibility,
            'authorLabel' => trim($authorLabel),
            'subject' => null !== $subject ? trim($subject) : null,
            'message' => null !== $message ? trim($message) : null,
            'status' => $status,
            'attachments' => $attachments,
            'createdAt' => ($createdAt ?? new \DateTimeImmutable())->format(DATE_ATOM),
        ];
    }

    /** @param list<array<string, mixed>> $attachments */
    private function normalizeAttachments(array $attachments): array
    {
        return array_values(array_filter(array_map(static function (array $attachment): ?array {
            $name = isset($attachment['name']) && is_string($attachment['name']) ? trim($attachment['name']) : '';
            $originalName = isset($attachment['originalName']) && is_string($attachment['originalName']) ? trim($attachment['originalName']) : '';
            if ('' === $name || '' === $originalName) {
                return null;
            }

            return [
                'name' => $name,
                'originalName' => $originalName,
                'contentType' => isset($attachment['contentType']) && is_string($attachment['contentType']) ? trim($attachment['contentType']) : 'application/octet-stream',
                'size' => isset($attachment['size']) && is_numeric($attachment['size']) ? (int) $attachment['size'] : 0,
                'uploadedAt' => isset($attachment['uploadedAt']) && is_string($attachment['uploadedAt']) ? $attachment['uploadedAt'] : (new \DateTimeImmutable())->format(DATE_ATOM),
            ];
        }, $attachments), static fn (?array $attachment): bool => null !== $attachment));
    }
}
