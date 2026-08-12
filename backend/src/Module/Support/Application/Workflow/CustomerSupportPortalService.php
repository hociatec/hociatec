<?php

declare(strict_types=1);

namespace App\Module\Support\Application\Workflow;

use App\Module\Admin\Application\Operations\Exception\OperationsResourceNotFoundException;
use App\Module\Admin\Application\Operations\Projection\AdminOperationsFormatter;
use App\Module\Order\Application\Port\OrderRepositoryPort;
use App\Module\Order\Domain\Entity\Order;
use App\Module\Support\Application\DTO\SupportCreateData;
use App\Module\Support\Application\Port\SupportRequestRepositoryPort;
use App\Module\Support\Domain\Entity\SupportRequest;
use App\Module\Support\Infrastructure\Storage\SupportAttachmentStorage;
use App\Module\User\Domain\Entity\User;

final readonly class CustomerSupportPortalService
{
    public function __construct(
        private SupportRequestRepositoryPort $supportRequests,
        private OrderRepositoryPort $orders,
        private SupportRequestService $supportService,
        private AdminOperationsFormatter $formatter,
        private SupportAttachmentStorage $attachmentStorage,
    ) {
    }

    /** @return array{items:list<array<string,mixed>>,total:int} */
    public function listForUser(User $user, int $limit = 10, int $offset = 0): array
    {
        $items = $this->supportRequests->findBy(
            ['customer' => $user],
            ['updatedAt' => 'DESC'],
            max(1, min(100, $limit)),
            max(0, $offset),
        );

        return [
            'items' => array_map($this->formatter->customerSupportRequest(...), $items),
            'total' => $this->supportRequests->count(['customer' => $user]),
        ];
    }

    /** @return array<string,mixed> */
    public function showForUser(User $user, int $supportId): array
    {
        return $this->formatter->customerSupportRequest($this->findForUser($user, $supportId));
    }

    /** @return array<string,mixed> */
    public function createForUser(User $user, SupportCreateData $data, array $files = []): array
    {
        $order = null !== $data->orderId ? $this->orders->find($data->orderId) : null;
        if (null !== $data->orderId && (!$order instanceof Order || $order->getUser()->getId() !== $user->getId())) {
            throw new OperationsResourceNotFoundException('Commande introuvable.');
        }

        $attachments = $this->attachmentStorage->store($files);
        $support = $this->supportService->create($user, $data, $order instanceof Order ? $order : null, $attachments);

        return $this->formatter->customerSupportRequest($support);
    }

    /** @return array<string,mixed> */
    public function replyForUser(User $user, int $supportId, string $message, ?string $subject = null, array $files = []): array
    {
        $support = $this->findForUser($user, $supportId);
        $attachments = $this->attachmentStorage->store($files);
        $support = $this->supportService->replyFromCustomer($support, $user, $message, $subject, $attachments);

        return $this->formatter->customerSupportRequest($support);
    }

    private function findForUser(User $user, int $supportId): SupportRequest
    {
        $support = $this->supportRequests->find($supportId);
        if (!$support instanceof SupportRequest || $support->getCustomer()->getId() !== $user->getId()) {
            throw new OperationsResourceNotFoundException('Demande SAV introuvable.');
        }

        return $support;
    }
}
