<?php

declare(strict_types=1);

namespace App\Module\Order\Domain\Entity;

use App\Module\Order\Domain\Enum\DeliveryStatus;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
final class OrderDelivery
{
    #[ORM\Column(length: 180, nullable: true)]
    private ?string $shippingName = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $shippingAddress = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $shippingPostalCode = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $shippingCity = null;

    #[ORM\Column(name: 'delivery_status', length: 30, enumType: DeliveryStatus::class, options: ['default' => DeliveryStatus::PREPARING->value])]
    private DeliveryStatus $status = DeliveryStatus::PREPARING;

    #[ORM\Column(name: 'delivery_carrier', length: 120, nullable: true)]
    private ?string $carrier = null;

    #[ORM\Column(name: 'delivery_tracking_number', length: 120, nullable: true)]
    private ?string $trackingNumber = null;

    #[ORM\Column(name: 'delivery_tracking_url', length: 255, nullable: true)]
    private ?string $trackingUrl = null;

    #[ORM\Column(name: 'delivery_estimated_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $estimatedAt = null;

    #[ORM\Column(name: 'delivery_shipped_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $shippedAt = null;

    #[ORM\Column(name: 'delivery_delivered_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $deliveredAt = null;

    public function getShippingName(): ?string
    {
        return $this->shippingName;
    }

    public function setShippingName(?string $name): self
    {
        $this->shippingName = $name;

        return $this;
    }

    public function getShippingAddress(): ?string
    {
        return $this->shippingAddress;
    }

    public function setShippingAddress(?string $address): self
    {
        $this->shippingAddress = $address;

        return $this;
    }

    public function getShippingPostalCode(): ?string
    {
        return $this->shippingPostalCode;
    }

    public function setShippingPostalCode(?string $code): self
    {
        $this->shippingPostalCode = $code;

        return $this;
    }

    public function getShippingCity(): ?string
    {
        return $this->shippingCity;
    }

    public function setShippingCity(?string $city): self
    {
        $this->shippingCity = $city;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status->value;
    }

    public function setStatus(string $status): self
    {
        $this->status = DeliveryStatus::from($status);

        return $this;
    }

    public function getCarrier(): ?string
    {
        return $this->carrier;
    }

    public function setCarrier(?string $carrier): self
    {
        $this->carrier = $carrier;

        return $this;
    }

    public function getTrackingNumber(): ?string
    {
        return $this->trackingNumber;
    }

    public function setTrackingNumber(?string $trackingNumber): self
    {
        $this->trackingNumber = $trackingNumber;

        return $this;
    }

    public function getTrackingUrl(): ?string
    {
        return $this->trackingUrl;
    }

    public function setTrackingUrl(?string $trackingUrl): self
    {
        $this->trackingUrl = $trackingUrl;

        return $this;
    }

    public function getEstimatedAt(): ?\DateTimeImmutable
    {
        return $this->estimatedAt;
    }

    public function setEstimatedAt(?\DateTimeImmutable $estimatedAt): self
    {
        $this->estimatedAt = $estimatedAt;

        return $this;
    }

    public function getShippedAt(): ?\DateTimeImmutable
    {
        return $this->shippedAt;
    }

    public function setShippedAt(?\DateTimeImmutable $shippedAt): self
    {
        $this->shippedAt = $shippedAt;

        return $this;
    }

    public function getDeliveredAt(): ?\DateTimeImmutable
    {
        return $this->deliveredAt;
    }

    public function setDeliveredAt(?\DateTimeImmutable $deliveredAt): self
    {
        $this->deliveredAt = $deliveredAt;

        return $this;
    }
}
