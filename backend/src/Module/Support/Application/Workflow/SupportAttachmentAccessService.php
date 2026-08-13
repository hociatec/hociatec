<?php

declare(strict_types=1);

namespace App\Module\Support\Application\Workflow;

use App\Module\Admin\Application\Operations\Exception\OperationsResourceNotFoundException;
use App\Module\Support\Application\Port\SupportAttachmentStoragePort;
use App\Module\Support\Application\Port\SupportRequestRepositoryPort;
use App\Module\Support\Domain\Entity\SupportRequest;
use App\Module\User\Domain\Entity\User;

final readonly class SupportAttachmentAccessService
{
    public function __construct(
        private SupportRequestRepositoryPort $supportRequests,
        private SupportAttachmentStoragePort $storage,
    ) {
    }

    public function pathForUser(User $user, int $supportId, string $name): ?string
    {
        $support = $this->findSupport($supportId);
        if ($support->getCustomer()->getId() !== $user->getId()) {
            throw new \DomainException('Accès refusé.');
        }

        return $this->attachmentPath($support, $name);
    }

    public function pathForAdmin(int $supportId, string $name): ?string
    {
        return $this->attachmentPath($this->findSupport($supportId), $name);
    }

    private function findSupport(int $supportId): SupportRequest
    {
        $support = $this->supportRequests->find($supportId);
        if (!$support instanceof SupportRequest) {
            throw new OperationsResourceNotFoundException('Demande SAV introuvable.');
        }

        return $support;
    }

    private function attachmentPath(SupportRequest $support, string $name): ?string
    {
        foreach ($support->getAttachments() as $attachment) {
            if (($attachment['name'] ?? null) === $name) {
                return $this->storage->path($name);
            }
        }

        return null;
    }
}
