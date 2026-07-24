<?php

declare(strict_types=1);

namespace App\Module\Loyalty\Service;

use App\Module\Order\Entity\Order;
use App\Module\User\Entity\User;
use App\Module\Voucher\Entity\Voucher;
use App\Module\Voucher\Service\VoucherManager;
use Doctrine\ORM\EntityManagerInterface;

final class LoyaltyService
{
    public const EARNING_POINTS_PER_EURO = 10;
    public const POINTS_PER_EURO = 100;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly VoucherManager $voucherManager,
    ) {
    }

    public function pointsToCents(int $points): int
    {
        return intdiv(max(0, $points), self::POINTS_PER_EURO) * 100;
    }

    public function centsToPoints(int $cents): int
    {
        return intdiv(max(0, $cents), 100) * self::POINTS_PER_EURO;
    }

    public function calculateEarnedPoints(Order $order): int
    {
        return intdiv($order->getTotalPriceCents(), 100) * self::EARNING_POINTS_PER_EURO;
    }

    public function syncOrderPoints(Order $order): void
    {
        $expected = in_array($order->getStatus(), [Order::STATUS_CONFIRMED, Order::STATUS_DELIVERED], true)
            ? $this->calculateEarnedPoints($order)
            : 0;
        $delta = $expected - $order->getLoyaltyPointsAwarded();

        if (0 === $delta) {
            return;
        }

        $order->setLoyaltyPointsAwarded($expected);
        $order->getUser()->addLoyaltyPoints($delta);
    }

    public function adjustBalance(User $user, int $points): void
    {
        $user->setLoyaltyPointsBalance($points);
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    public function convertPointsToVoucher(User $user, int $points): Voucher
    {
        $points = max(0, $points);
        $cents = $this->pointsToCents($points);
        $normalizedPoints = $this->centsToPoints($cents);

        if ($normalizedPoints <= 0 || $cents <= 0) {
            throw new \InvalidArgumentException('Le montant à convertir doit représenter au moins 1 €.');
        }

        if ($normalizedPoints > $user->getLoyaltyPointsBalance()) {
            throw new \InvalidArgumentException('Solde de fidélité insuffisant.');
        }

        $voucher = $this->voucherManager->create([
            'name' => 'Conversion fidélité',
            'code' => $this->generateVoucherCode($user),
            'description' => sprintf('%d points fidélité convertis en bon de réduction.', $normalizedPoints),
            'discountType' => Voucher::TYPE_FIXED_CENTS,
            'discountValue' => $cents,
            'isActive' => true,
            'startsAt' => new \DateTimeImmutable(),
            'endsAt' => (new \DateTimeImmutable())->modify('+12 months'),
        ]);

        $voucher
            ->setRecipientUserId($user->getId())
            ->setRecipientEmail($user->getEmail());

        $user->addLoyaltyPoints(-$normalizedPoints);
        $this->entityManager->persist($voucher);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $voucher;
    }

    private function generateVoucherCode(User $user): string
    {
        $seed = preg_replace('/[^A-Za-z0-9]+/', '', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $user->getLastName()) ?: '') ?: 'CLIENT';

        return sprintf('FID-%s-%s', strtoupper(substr($seed, 0, 10)), strtoupper(substr(bin2hex(random_bytes(3)), 0, 6)));
    }
}
