<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Audit\DTO;

use App\Module\Audit\Domain\Entity\AuditRequest;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class AuditStatusInput
{
    public function __construct(#[Assert\Choice(choices: [AuditRequest::STATUS_NEW, AuditRequest::STATUS_IN_PROGRESS, AuditRequest::STATUS_REVIEW, AuditRequest::STATUS_DONE])] public string $status)
    {
    }

    /** @param array<string,mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(is_string($payload['status'] ?? null) ? trim($payload['status']) : '');
    }
}
