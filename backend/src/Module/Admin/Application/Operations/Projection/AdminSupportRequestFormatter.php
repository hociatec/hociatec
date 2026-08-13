<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Operations\Projection;

use App\Module\Admin\Application\Operations\DTO\SupportRequestOutput;
use App\Module\Support\Domain\Entity\SupportRequest;

final readonly class AdminSupportRequestFormatter
{
    public function __construct(private AdminSupportTimelineFormatter $timeline)
    {
    }

    public function supportRequest(SupportRequest $support): SupportRequestOutput
    {
        return new SupportRequestOutput($this->basePayload($support, true, true));
    }

    /**
     * @return array{
     *   id: int|null,
     *   status: string,
     *   statusLabel: string,
     *   reason: string,
     *   subject: string,
     *   message: string|null,
     *   internalNotes: string|null,
     *   customer: array{id: int|null, name: string, email: string},
     *   order: array{id: int|null, number: string|null}|null,
     *   attachments: list<array{name:string,originalName:string,contentType:string,size:int,uploadedAt:string}>,
     *   awaitingReplyFrom: string|null,
     *   awaitingReplyLabel: string|null,
     *   timeline: list<array{
     *     id:string,
     *     type:string,
     *     actor:string,
     *     visibility:string,
     *     authorLabel:string,
     *     subject:string|null,
     *     message:string|null,
     *     status:string|null,
     *     statusLabel:string|null,
     *     attachments:list<array{name:string,originalName:string,contentType:string,size:int,uploadedAt:string}>,
     *     createdAt:string
     *   }>,
     *   createdAt: string,
     *   updatedAt: string,
     *   resolvedAt: string|null
     * }
     */
    public function customerSupportRequest(SupportRequest $support): array
    {
        return $this->basePayload($support, false, false);
    }

    public function awaitingReplyFrom(SupportRequest $support): ?string
    {
        if (in_array($support->getStatus(), [SupportRequest::STATUS_RESOLVED, SupportRequest::STATUS_REFUSED], true)) {
            return null;
        }

        $timeline = $this->timeline->timeline($support, false);
        $lastEntry = [] !== $timeline ? $timeline[array_key_last($timeline)] : null;
        $lastActor = null !== $lastEntry ? $lastEntry['actor'] : null;

        return 'admin' === $lastActor ? 'customer' : 'admin';
    }

    public function awaitingReplyLabel(SupportRequest $support): ?string
    {
        return match ($this->awaitingReplyFrom($support)) {
            'admin' => 'Réponse admin attendue',
            'customer' => 'Réponse client attendue',
            default => null,
        };
    }

    public static function statusLabel(string $status): string
    {
        return match ($status) {
            SupportRequest::STATUS_NEW => 'Nouveau',
            SupportRequest::STATUS_IN_PROGRESS => 'En cours',
            SupportRequest::STATUS_WAITING_CUSTOMER => 'En attente client',
            SupportRequest::STATUS_RESOLVED => 'Résolu',
            SupportRequest::STATUS_REFUSED => 'Refusé',
            default => $status,
        };
    }

    /**
     * @return array{
     *   id: int|null,
     *   status: string,
     *   statusLabel: string,
     *   reason: string,
     *   subject: string,
     *   message: string|null,
     *   internalNotes: string|null,
     *   customer: array{id: int|null, name: string, email: string},
     *   order: array{id: int|null, number: string|null}|null,
     *   attachments: list<array{name:string,originalName:string,contentType:string,size:int,uploadedAt:string}>,
     *   awaitingReplyFrom: string|null,
     *   awaitingReplyLabel: string|null,
     *   timeline: list<array{
     *     id:string,
     *     type:string,
     *     actor:string,
     *     visibility:string,
     *     authorLabel:string,
     *     subject:string|null,
     *     message:string|null,
     *     status:string|null,
     *     statusLabel:string|null,
     *     attachments:list<array{name:string,originalName:string,contentType:string,size:int,uploadedAt:string}>,
     *     createdAt:string
     *   }>,
     *   createdAt: string,
     *   updatedAt: string,
     *   resolvedAt: string|null
     * }
     */
    private function basePayload(SupportRequest $support, bool $includeInternal, bool $withInternalNotes): array
    {
        $customer = $support->getCustomer();
        $orderId = $support->getOrderId();
        $orderNumber = $support->getOrderNumber();

        return [
            'id' => $support->getId(),
            'status' => $support->getStatus(),
            'statusLabel' => self::statusLabel($support->getStatus()),
            'reason' => $support->getReason(),
            'subject' => $support->getSubject(),
            'message' => $support->getMessage(),
            'internalNotes' => $withInternalNotes ? $support->getInternalNotes() : null,
            'customer' => ['id' => $customer->getId(), 'name' => $customer->getFullName(), 'email' => $customer->getEmail()],
            'order' => null !== $orderId || null !== $orderNumber ? ['id' => $orderId, 'number' => $orderNumber] : null,
            'attachments' => $this->attachments($support),
            'awaitingReplyFrom' => $this->awaitingReplyFrom($support),
            'awaitingReplyLabel' => $this->awaitingReplyLabel($support),
            'timeline' => $this->timeline->timeline($support, $includeInternal),
            'createdAt' => $support->getCreatedAt()->format(DATE_ATOM),
            'updatedAt' => $support->getUpdatedAt()->format(DATE_ATOM),
            'resolvedAt' => $support->getResolvedAt()?->format(DATE_ATOM),
        ];
    }

    /** @return list<array{name:string,originalName:string,contentType:string,size:int,uploadedAt:string}> */
    private function attachments(SupportRequest $support): array
    {
        return array_values(array_filter(array_map(function (mixed $attachment) use ($support): ?array {
            if (!is_array($attachment)) {
                return null;
            }

            $name = isset($attachment['name']) && is_string($attachment['name']) ? trim($attachment['name']) : '';
            $originalName = isset($attachment['originalName']) && is_string($attachment['originalName']) ? trim($attachment['originalName']) : '';
            if ('' === $name || '' === $originalName) {
                return null;
            }

            return [
                'name' => $name,
                'originalName' => $originalName,
                'contentType' => isset($attachment['contentType']) && is_string($attachment['contentType']) ? $attachment['contentType'] : 'application/octet-stream',
                'size' => isset($attachment['size']) && is_numeric($attachment['size']) ? (int) $attachment['size'] : 0,
                'uploadedAt' => isset($attachment['uploadedAt']) && is_string($attachment['uploadedAt']) ? $attachment['uploadedAt'] : $support->getCreatedAt()->format(DATE_ATOM),
            ];
        }, $support->getAttachments()), static fn (?array $attachment): bool => null !== $attachment));
    }
}
