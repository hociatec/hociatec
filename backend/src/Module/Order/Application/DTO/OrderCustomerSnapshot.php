<?php

declare(strict_types=1);

namespace App\Module\Order\Application\DTO;

use App\Module\Order\Domain\Entity\Order;

final readonly class OrderCustomerSnapshot
{
    public function __construct(
        public ?int $id,
        public string $firstName,
        public string $lastName,
        public string $fullName,
        public string $email,
        public string $phoneNumber,
    ) {
    }

    public static function fromOrder(Order $order): self
    {
        $user = $order->getUser();

        return new self(
            $user->getId(),
            $user->getFirstName(),
            $user->getLastName(),
            $user->getFullName(),
            $user->getEmail(),
            $user->getPhoneNumber(),
        );
    }

    public function displayName(): string
    {
        return trim($this->firstName.' '.$this->lastName);
    }
}
