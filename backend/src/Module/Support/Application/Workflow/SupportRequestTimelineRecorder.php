<?php

declare(strict_types=1);

namespace App\Module\Support\Application\Workflow;

use App\Module\Support\Application\DTO\SupportUpdateData;
use App\Module\Support\Domain\Entity\SupportRequest;
use App\Module\User\Domain\Entity\User;

final readonly class SupportRequestTimelineRecorder
{
    public function __construct(
        private SupportTimelineEntryFactory $timelineEntries,
    ) {
    }

    /** @param list<array<string, mixed>> $initialAttachments */
    public function recordCreation(SupportRequest $support, User $customer, array $initialAttachments): void
    {
        if (null !== $support->getMessage() && '' !== $support->getMessage()) {
            $support->appendTimelineEntry($this->timelineEntries->create(
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
            $support->appendTimelineEntry($this->timelineEntries->create(
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
    }

    public function recordAdminUpdate(
        SupportRequest $support,
        SupportUpdateData $data,
        string $previousStatus,
        ?string $previousInternalNotes,
        string $previousSubject,
    ): void {
        if (null !== $data->subject && $support->getSubject() !== $previousSubject) {
            $support->appendTimelineEntry($this->timelineEntries->create(
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
            $support->appendTimelineEntry($this->timelineEntries->create(
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
            $support->appendTimelineEntry($this->timelineEntries->create(
                'status_change',
                'admin',
                'customer',
                'Équipe Hociatec',
                'Statut mis à jour',
                null,
                $data->status,
            ));
        }
    }

    public function recordAdminReply(
        SupportRequest $support,
        string $subject,
        string $message,
        string $previousStatus,
        \DateTimeImmutable $now,
    ): void {
        $support->appendTimelineEntry($this->timelineEntries->create(
            'admin_reply',
            'admin',
            'customer',
            'Équipe Hociatec',
            $subject,
            $message,
            null,
            $now,
            [],
        ));

        if ($support->getStatus() !== $previousStatus) {
            $support->appendTimelineEntry($this->timelineEntries->create(
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
    }

    /** @param list<array<string, mixed>> $normalizedAttachments */
    public function recordCustomerReply(
        SupportRequest $support,
        User $customer,
        string $subject,
        string $message,
        string $previousStatus,
        \DateTimeImmutable $now,
        array $normalizedAttachments,
    ): void {
        $support->appendTimelineEntry($this->timelineEntries->create(
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
            $support->appendTimelineEntry($this->timelineEntries->create(
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
    }
}
