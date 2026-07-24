<?php

declare(strict_types=1);

namespace App\Module\Admin\Operations\Service;

use App\Module\Admin\Operations\Exception\OperationsResourceNotFoundException;
use App\Module\Order\Entity\Order;
use App\Module\Order\Repository\OrderRepository;
use App\Module\Support\Entity\SupportRequest;
use App\Module\Support\Repository\SupportRequestRepository;
use App\Module\User\Entity\User;
use App\Module\User\Repository\UserRepository;
use App\Module\User\Service\AdminCustomerEmailService;
use Doctrine\ORM\EntityManagerInterface;

final readonly class SupportOperationsService
{
    public function __construct(
        private SupportRequestRepository $supportRequests,
        private UserRepository $users,
        private OrderRepository $orders,
        private AdminCustomerEmailService $customerEmails,
        private EntityManagerInterface $entityManager,
        private AdminOperationsFormatter $formatter,
    ) {
    }

    /** @return list<array<string, mixed>> */
    public function list(): array
    {
        return array_map($this->formatter->supportRequest(...), $this->supportRequests->findBy([], ['updatedAt' => 'DESC']));
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function create(array $payload): array
    {
        $customer = $this->users->find((int) ($payload['customerId'] ?? 0));
        if (!$customer instanceof User) {
            throw new OperationsResourceNotFoundException('Client introuvable.');
        }

        $support = new SupportRequest($customer, (string) ($payload['subject'] ?? 'Demande SAV'));
        $support
            ->setReason((string) ($payload['reason'] ?? 'other'))
            ->setMessage(isset($payload['message']) ? (string) $payload['message'] : null)
            ->setInternalNotes(isset($payload['internalNotes']) ? (string) $payload['internalNotes'] : null);

        $order = $this->orders->find((int) ($payload['orderId'] ?? 0));
        if ($order instanceof Order) {
            $support->setOrder($order);
        }

        $this->entityManager->persist($support);
        $this->entityManager->flush();

        return $this->formatter->supportRequest($support);
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function update(int $supportId, array $payload): array
    {
        $support = $this->findSupport($supportId);
        if (isset($payload['status'])) {
            $support->setStatus((string) $payload['status']);
        }
        if (array_key_exists('internalNotes', $payload)) {
            $support->setInternalNotes(null !== $payload['internalNotes'] ? (string) $payload['internalNotes'] : null);
        }
        if (isset($payload['subject'])) {
            $support->setSubject((string) $payload['subject']);
        }
        $this->entityManager->flush();

        return $this->formatter->supportRequest($support);
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function reply(int $supportId, array $payload): array
    {
        $support = $this->findSupport($supportId);
        $subject = trim((string) ($payload['subject'] ?? ('Réponse à votre demande SAV #'.$support->getId())));
        $message = trim((string) ($payload['message'] ?? ''));
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
            ->setStatus((string) ($payload['status'] ?? SupportRequest::STATUS_WAITING_CUSTOMER));
        $this->entityManager->flush();

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
