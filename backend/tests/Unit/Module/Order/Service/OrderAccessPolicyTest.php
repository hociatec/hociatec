<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Order\Service;

use App\Module\Order\Domain\Entity\Order;
use App\Module\Order\Domain\Security\OrderAccessPolicy;
use App\Module\User\Domain\Entity\User;
use PHPUnit\Framework\TestCase;

final class OrderAccessPolicyTest extends TestCase
{
    public function testOrderPermissionsHaveDistinctBusinessRules(): void
    {
        $owner = $this->user('owner@example.com');
        $other = $this->user('other@example.com');
        $order = new Order('ORD-1', $owner);
        $policy = new OrderAccessPolicy();

        self::assertTrue($policy->canView($owner, $order));
        self::assertFalse($policy->canView($other, $order));
        self::assertTrue($policy->canCheckout($owner, $order));
        self::assertTrue($policy->canCancel($owner, $order));
        self::assertTrue($policy->canDownloadInvoice($owner, $order));

        $order->setStatus(Order::STATUS_CONFIRMED);
        self::assertFalse($policy->canCheckout($owner, $order));
        self::assertTrue($policy->canCancel($owner, $order));

        $order->setDeliveryStatus(Order::DELIVERY_STATUS_SHIPPED);
        self::assertFalse($policy->canCancel($owner, $order));

        $order->setDeliveryStatus(Order::DELIVERY_STATUS_PREPARING);
        $order->setStatus(Order::STATUS_CANCELLED);
        self::assertFalse($policy->canCancel($owner, $order));
        self::assertFalse($policy->canDownloadInvoice($owner, $order));
    }

    private function user(string $email): User
    {
        return new User($email, 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
    }
}
