<?php

declare(strict_types=1);

namespace App\Module\Order\Domain\Entity;

use App\Module\Order\Domain\ValueObject\CheckoutShippingAddress;

trait OrderCheckoutSnapshotStateTrait
{
    private function customerSnapshot(): CheckoutCustomerSnapshot
    {
        return new CheckoutCustomerSnapshot($this->customerFullName, $this->customerEmail);
    }

    private function shippingSnapshot(): CheckoutShippingSnapshot
    {
        return new CheckoutShippingSnapshot(
            new CheckoutShippingAddress(
                $this->shippingName,
                $this->shippingAddress,
                $this->shippingPostalCode,
                $this->shippingCity,
            ),
        );
    }

    private function billingSnapshot(): CheckoutBillingSnapshot
    {
        return CheckoutBillingSnapshot::fromScalars(
            $this->billingName,
            $this->billingCompany,
            $this->billingCompanySiren,
            $this->billingCompanyVatNumber,
            $this->purchaseOrderNumber,
            $this->billingEmail,
            $this->billingAddress,
            $this->billingPostalCode,
            $this->billingCity,
        );
    }
}
