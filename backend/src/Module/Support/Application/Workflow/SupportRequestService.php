<?php

declare(strict_types=1);

namespace App\Module\Support\Application\Workflow;

use App\Module\Order\Domain\Entity\Order;
use App\Module\Support\Application\DTO\SupportCreateData;
use App\Module\Support\Application\DTO\SupportReplyData;
use App\Module\Support\Application\Port\SupportCustomerMessengerPort;
use App\Module\Support\Application\DTO\SupportUpdateData;
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

    public function create(User $customer, SupportCreateData $data, ?Order $order = null): SupportRequest
    {
        $support = new SupportRequest($customer, $data->subject);
        $support
            ->setReason($data->reason)
            ->setMessage($data->message)
            ->setInternalNotes($data->internalNotes);

        if ($order instanceof Order) {
            $support->setOrderId($order->getId(), $order->getNumber());
        } else {
            $support->setOrderId(null);
        }

        $this->persistence->persist($support);
        $this->persistence->flush();

        return $support;
    }

    public function update(SupportRequest $support, SupportUpdateData $data): SupportRequest
    {
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

        $this->persistence->flush();

        return $support;
    }

    public function reply(SupportRequest $support, SupportReplyData $data): SupportRequest
    {
        if (!$this->messenger instanceof SupportCustomerMessengerPort) {
            throw new \LogicException('Support customer messenger is not configured.');
        }

        $subject = trim($data->subject ?? ('Réponse à votre demande SAV #'.$support->getId()));
        $message = trim($data->message);
        if ('' === $message) {
            throw new \InvalidArgumentException('Le message de réponse est obligatoire.');
        }

        $this->messenger->send($support->getCustomer(), $subject, $message);
        $note = trim(sprintf(
            "%s\n[%s] Réponse envoyée au client : %s",
            (string) $support->getInternalNotes(),
            (new \DateTimeImmutable())->format('d/m/Y H:i'),
            $subject,
        ));
        $support
            ->setInternalNotes($note)
            ->setStatus($data->status ?? SupportRequest::STATUS_WAITING_CUSTOMER);
        $this->persistence->flush();

        return $support;
    }
}
