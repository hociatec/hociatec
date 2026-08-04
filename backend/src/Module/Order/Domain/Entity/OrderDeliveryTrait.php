<?php

declare(strict_types=1);

namespace App\Module\Order\Domain\Entity;

trait OrderDeliveryTrait
{
    public function getShippingName(): ?string
    {
        return $this->delivery->getShippingName();
    }

    public function setShippingName(?string $name): self
    {
        $this->delivery->setShippingName($name);

        return $this;
    }

    public function getShippingAddress(): ?string
    {
        return $this->delivery->getShippingAddress();
    }

    public function setShippingAddress(?string $address): self
    {
        $this->delivery->setShippingAddress($address);

        return $this;
    }

    public function getShippingPostalCode(): ?string
    {
        return $this->delivery->getShippingPostalCode();
    }

    public function setShippingPostalCode(?string $code): self
    {
        $this->delivery->setShippingPostalCode($code);

        return $this;
    }

    public function getShippingCity(): ?string
    {
        return $this->delivery->getShippingCity();
    }

    public function setShippingCity(?string $city): self
    {
        $this->delivery->setShippingCity($city);

        return $this;
    }

    public function getDeliveryStatus(): string
    {
        return $this->delivery->getStatus();
    }

    public function setDeliveryStatus(string $deliveryStatus): self
    {
        $this->delivery->setStatus($deliveryStatus);

        return $this;
    }

    public function getDeliveryCarrier(): ?string
    {
        return $this->delivery->getCarrier();
    }

    public function setDeliveryCarrier(?string $deliveryCarrier): self
    {
        $this->delivery->setCarrier($deliveryCarrier);

        return $this;
    }

    public function getDeliveryTrackingNumber(): ?string
    {
        return $this->delivery->getTrackingNumber();
    }

    public function setDeliveryTrackingNumber(?string $deliveryTrackingNumber): self
    {
        $this->delivery->setTrackingNumber($deliveryTrackingNumber);

        return $this;
    }

    public function getDeliveryTrackingUrl(): ?string
    {
        return $this->delivery->getTrackingUrl();
    }

    public function setDeliveryTrackingUrl(?string $deliveryTrackingUrl): self
    {
        $this->delivery->setTrackingUrl($deliveryTrackingUrl);

        return $this;
    }

    public function getDeliveryEstimatedAt(): ?\DateTimeImmutable
    {
        return $this->delivery->getEstimatedAt();
    }

    public function setDeliveryEstimatedAt(?\DateTimeImmutable $deliveryEstimatedAt): self
    {
        $this->delivery->setEstimatedAt($deliveryEstimatedAt);

        return $this;
    }

    public function getDeliveryShippedAt(): ?\DateTimeImmutable
    {
        return $this->delivery->getShippedAt();
    }

    public function setDeliveryShippedAt(?\DateTimeImmutable $deliveryShippedAt): self
    {
        $this->delivery->setShippedAt($deliveryShippedAt);

        return $this;
    }

    public function getDeliveryDeliveredAt(): ?\DateTimeImmutable
    {
        return $this->delivery->getDeliveredAt();
    }

    public function setDeliveryDeliveredAt(?\DateTimeImmutable $deliveryDeliveredAt): self
    {
        $this->delivery->setDeliveredAt($deliveryDeliveredAt);

        return $this;
    }
}
