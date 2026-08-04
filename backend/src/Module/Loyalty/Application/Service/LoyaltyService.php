<?php

declare(strict_types=1);

namespace App\Module\Loyalty\Application\Service;

use App\Infrastructure\Persistence\DoctrinePersistence;
use App\Module\Loyalty\Domain\Exception\LoyaltyOperationException;
use App\Module\Order\Domain\Entity\Order;
use App\Module\User\Domain\Entity\User;
use App\Module\User\Infrastructure\Repository\UserRepository;
use App\Module\Voucher\Application\Service\VoucherManager;
use App\Module\Voucher\Domain\Entity\Voucher;

final class LoyaltyService
{
    public const EARNING_POINTS_PER_EURO = 10;
    public const POINTS_PER_EURO = 100;

    public function __construct(
        private readonly DoctrinePersistence $persistence,
        private readonly VoucherManager $voucherManager,
        private readonly UserRepository $users,
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

    /** @return list<User> */
    public function findCustomers(string $search, int $limit, int $offset): array
    {
        $qb = $this->users->createQueryBuilder('u')
            ->orderBy('u.loyaltyPointsBalance', 'DESC')
            ->addOrderBy('u.createdAt', 'DESC')
            ->setMaxResults(max(1, min(100, $limit)))
            ->setFirstResult(max(0, $offset));

        if ('' !== $search) {
            $qb
                ->andWhere('LOWER(u.email) LIKE LOWER(:search) OR LOWER(u.firstName) LIKE LOWER(:search) OR LOWER(u.lastName) LIKE LOWER(:search)')
                ->setParameter('search', '%'.$search.'%');
        }

        return $qb->getQuery()->getResult();
    }

    public function countCustomers(string $search): int
    {
        $qb = $this->users->createQueryBuilder('u')->select('COUNT(u.id)');
        if ('' !== $search) {
            $qb
                ->andWhere('LOWER(u.email) LIKE LOWER(:search) OR LOWER(u.firstName) LIKE LOWER(:search) OR LOWER(u.lastName) LIKE LOWER(:search)')
                ->setParameter('search', '%'.$search.'%');
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
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

        try {
            $this->persistence->persist($user);
            $this->persistence->flush();
        } catch (\RuntimeException $exception) {
            throw LoyaltyOperationException::failed('Impossible de mettre à jour le solde fidélité.', $exception);
        }
    }

    public function convertPointsToVoucher(User $user, int $points): Voucher
    {
        try {
            return $this->persistence->transactional(
                fn (): Voucher => $this->convertPointsToVoucherInTransaction($this->lockUser($user), $points),
            );
        } catch (\RuntimeException $exception) {
            throw LoyaltyOperationException::failed('Impossible de convertir les points fidélité.', $exception);
        }
    }

    private function lockUser(User $user): User
    {
        $userId = $user->getId();
        if (null === $userId) {
            return $user;
        }

        $locked = $this->persistence->findForUpdate(User::class, $userId);
        if (!$locked instanceof User) {
            throw new \InvalidArgumentException('Client introuvable.');
        }

        return $locked;
    }

    private function convertPointsToVoucherInTransaction(User $user, int $points): Voucher
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
        $this->persistence->persist($voucher);
        $this->persistence->persist($user);
        $this->persistence->flush();

        return $voucher;
    }

    private function generateVoucherCode(User $user): string
    {
        $seed = preg_replace('/[^A-Za-z0-9]+/', '', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $user->getLastName()) ?: '') ?: 'CLIENT';

        try {
            $suffix = strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
        } catch (\Random\RandomException $exception) {
            throw LoyaltyOperationException::failed('Impossible de générer le code fidélité.', $exception);
        }

        return sprintf('FID-%s-%s', strtoupper(substr($seed, 0, 10)), $suffix);
    }
}
