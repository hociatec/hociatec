<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Operations\Workflow;

use App\Module\Admin\Application\Operations\Exception\OperationsResourceNotFoundException;
use App\Module\Admin\Application\Operations\Persistence\OperationsPersistence;
use App\Module\Admin\Application\Operations\Projection\AdminOperationsFormatter;
use App\Module\Order\Application\Port\OrderRepositoryPort;
use App\Module\Order\Domain\Entity\Order;
use App\Module\Support\Application\DTO\SupportCreateData;
use App\Module\Support\Application\DTO\SupportReplyData;
use App\Module\Support\Application\DTO\SupportUpdateData;
use App\Module\Support\Application\Port\SupportRequestRepositoryPort;
use App\Module\Support\Domain\Entity\SupportRequest;
use App\Module\Support\Domain\Enum\SupportStatus;
use App\Module\User\Application\Port\UserRepositoryPort;
use App\Module\User\Application\Workflow\AdminCustomerEmailService;
use App\Module\User\Domain\Entity\User;

final readonly class SupportOperationsService
{
    public function __construct(
        private SupportRequestRepositoryPort $supportRequests,
        private UserRepositoryPort $users,
        private OrderRepositoryPort $orders,
        private AdminCustomerEmailService $customerEmails,
        private OperationsPersistence $persistence,
        private AdminOperationsFormatter $formatter,
    ) {
    }

    /** @return list<array<string, mixed>> */
    public function list(int $limit = 20, int $offset = 0): array
    {
        return array_map($this->formatter->supportRequest(...), $this->supportRequests->findBy([], ['updatedAt' => 'DESC'], max(1, min(100, $limit)), max(0, $offset)));
    }

    public function count(): int
    {
        return $this->supportRequests->count([]);
    }

    /** @return array<string, mixed> */
    public function create(SupportCreateData $data): array
    {
        $customer = $this->users->find($data->customerId);
        if (!$customer instanceof User) {
            throw new OperationsResourceNotFoundException('Client introuvable.');
        }

        $support = new SupportRequest($customer, $data->subject);
        $support
            ->setReason($data->reason)
            ->setMessage($data->message)
            ->setInternalNotes($data->internalNotes);

        $order = null !== $data->orderId ? $this->orders->find($data->orderId) : null;
        if ($order instanceof Order) {
            $support->setOrder($order);
        }

        $this->persistence->persist($support);
        $this->persistence->commit();

        return $this->formatter->supportRequest($support);
    }

    /** @return array<string, mixed> */
    public function update(int $supportId, SupportUpdateData $data): array
    {
        $support = $this->findSupport($supportId);
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
        $this->persistence->commit();

        return $this->formatter->supportRequest($support);
    }

    /** @return array<string, mixed> */
    public function reply(int $supportId, SupportReplyData $data): array
    {
        $support = $this->findSupport($supportId);
        $subject = trim($data->subject ?? ('Réponse à votre demande SAV #'.$support->getId()));
        $message = trim($data->message);
        if ('' === $message) {
            throw new \InvalidArgumentException('Le message de réponse est obligatoire.');
        }

        $this->customerEmails->send($support->getCustomer(), $subject, $message);
        $note = trim(sprintf(
            "%s\n[%s] Réponse envoyée au client : %s",
            (string) $support->getInternalNotes(),
            (new \DateTimeImmutable())->format('d/m/Y H:i'),
            $subject,
        ));
        $support
            ->setInternalNotes($note)
            ->setStatus($data->status ?? SupportRequest::STATUS_WAITING_CUSTOMER);
        $this->persistence->commit();

        return $this->formatter->supportRequest($support);
    }

    private function findSupport(int $supportId): SupportRequest
    {
        $support = $this->supportRequests->find($supportId);
        if (!$support instanceof SupportRequest) {
            throw new OperationsResourceNotFoundException('Demande SAV introuvable.');
        }

        return $support;
    }
}
