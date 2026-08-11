<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Module\Order\Domain\Entity\Order;
use App\Module\User\Domain\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;

final class E2eOrderFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    private const PENDING_ORDER_NUMBER_CHROMIUM = 'ORD-E2E-PENDING-CHROMIUM';
    private const PENDING_ORDER_NUMBER_MOBILE = 'ORD-E2E-PENDING-MOBILE';
    private const CONFIRMED_ORDER_NUMBER = 'ORD-E2E-CONFIRMED';

    public function load(ObjectManager $manager): void
    {
        if (!$manager instanceof EntityManagerInterface) {
            throw new \LogicException('Les fixtures E2E nécessitent un entity manager Doctrine ORM.');
        }

        $user = $manager->createQueryBuilder()
            ->select('u')
            ->from(User::class, 'u')
            ->andWhere('LOWER(u.identity.email) = LOWER(:email)')
            ->setParameter('email', E2eUserFixtures::CLIENT_EMAIL)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
        if (!$user instanceof User) {
            return;
        }

        $this->upsertOrder(
            $manager,
            self::PENDING_ORDER_NUMBER_CHROMIUM,
            $user,
            Order::STATUS_PENDING,
            129900,
            'Client E2E',
            '10 rue de test',
            '75001',
            'Paris',
        );

        $this->upsertOrder(
            $manager,
            self::PENDING_ORDER_NUMBER_MOBILE,
            $user,
            Order::STATUS_PENDING,
            129900,
            'Client E2E',
            '10 rue de test',
            '75001',
            'Paris',
        );

        $this->upsertOrder(
            $manager,
            self::CONFIRMED_ORDER_NUMBER,
            $user,
            Order::STATUS_CONFIRMED,
            99900,
            'Client E2E',
            '10 rue de test',
            '75001',
            'Paris',
        );

        $manager->flush();
    }

    public static function getGroups(): array
    {
        return ['e2e'];
    }

    public function getDependencies(): array
    {
        return [E2eUserFixtures::class];
    }

    private function upsertOrder(
        ObjectManager $manager,
        string $number,
        User $user,
        string $status,
        int $totalPriceCents,
        string $shippingName,
        string $shippingAddress,
        string $shippingPostalCode,
        string $shippingCity,
    ): void {
        if (!$manager instanceof EntityManagerInterface) {
            throw new \LogicException('Les fixtures E2E nécessitent un entity manager Doctrine ORM.');
        }

        $order = $manager->getRepository(Order::class)->findOneBy(['number' => $number]);
        if (!$order instanceof Order) {
            $order = new Order($number, $user);
            $manager->persist($order);
        }

        $order
            ->setUser($user)
            ->setStatus($status)
            ->setSubtotalPriceCents($totalPriceCents)
            ->setDiscountAmountCents(0)
            ->setTotalPriceCents($totalPriceCents)
            ->setDeliveryStatus(Order::DELIVERY_STATUS_PREPARING)
            ->setInvoiceStatus(Order::INVOICE_STATUS_ISSUED)
            ->setInvoicedAt(new \DateTimeImmutable())
            ->setShippingName($shippingName)
            ->setShippingAddress($shippingAddress)
            ->setShippingPostalCode($shippingPostalCode)
            ->setShippingCity($shippingCity)
            ->setBillingName($shippingName)
            ->setBillingAddress($shippingAddress)
            ->setBillingPostalCode($shippingPostalCode)
            ->setBillingCity($shippingCity)
            ->setBillingEmail($user->getEmail());
    }
}
