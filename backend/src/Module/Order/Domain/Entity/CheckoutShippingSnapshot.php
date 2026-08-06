<?php

declare(strict_types=1);

namespace App\Module\Order\Domain\Entity;

use App\Module\Order\Domain\ValueObject\CheckoutShippingAddress;

final class CheckoutShippingSnapshot
{
    private CheckoutShippingAddress $data;

    public function __construct(CheckoutShippingAddress $data)
    {
        $this->data = $data;
    }

    public function name(): ?string
    {
        return $this->data->name;
    }

    public function address(): ?string
    {
        return $this->data->address;
    }

    public function postalCode(): ?string
    {
        return $this->data->postalCode;
    }

    public function city(): ?string
    {
        return $this->data->city;
    }
}
