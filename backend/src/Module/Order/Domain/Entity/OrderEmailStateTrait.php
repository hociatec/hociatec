<?php

declare(strict_types=1);

namespace App\Module\Order\Domain\Entity;

use Doctrine\ORM\Mapping as ORM;

trait OrderEmailStateTrait
{
    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $orderCreatedEmailSentAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $invoiceEmailSentAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $statusConfirmedEmailSentAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $statusDeliveredEmailSentAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $statusCancelledEmailSentAt = null;

    public function getOrderCreatedEmailSentAt(): ?\DateTimeImmutable
    {
        return $this->orderCreatedEmailSentAt;
    }

    public function setOrderCreatedEmailSentAt(?\DateTimeImmutable $sentAt): self
    {
        $this->orderCreatedEmailSentAt = $sentAt;

        return $this;
    }

    public function getInvoiceEmailSentAt(): ?\DateTimeImmutable
    {
        return $this->invoiceEmailSentAt;
    }

    public function setInvoiceEmailSentAt(?\DateTimeImmutable $sentAt): self
    {
        $this->invoiceEmailSentAt = $sentAt;

        return $this;
    }

    public function getStatusConfirmedEmailSentAt(): ?\DateTimeImmutable
    {
        return $this->statusConfirmedEmailSentAt;
    }

    public function setStatusConfirmedEmailSentAt(?\DateTimeImmutable $sentAt): self
    {
        $this->statusConfirmedEmailSentAt = $sentAt;

        return $this;
    }

    public function getStatusDeliveredEmailSentAt(): ?\DateTimeImmutable
    {
        return $this->statusDeliveredEmailSentAt;
    }

    public function setStatusDeliveredEmailSentAt(?\DateTimeImmutable $sentAt): self
    {
        $this->statusDeliveredEmailSentAt = $sentAt;

        return $this;
    }

    public function getStatusCancelledEmailSentAt(): ?\DateTimeImmutable
    {
        return $this->statusCancelledEmailSentAt;
    }

    public function setStatusCancelledEmailSentAt(?\DateTimeImmutable $sentAt): self
    {
        $this->statusCancelledEmailSentAt = $sentAt;

        return $this;
    }
}
