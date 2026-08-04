<?php

declare(strict_types=1);

namespace App\Module\Order\Application\Service;

use App\Infrastructure\Application\TransactionManager;
use App\Module\Order\Domain\Entity\Order;
use Doctrine\ORM\EntityManagerInterface;

final readonly class OrderPersistence implements TransactionManager
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function flush(): void
    {
        $this->entityManager->flush();
    }

    public function save(Order $order): void
    {
        $this->entityManager->persist($order);
        $this->entityManager->flush();
    }

    /**
     * @template T
     *
     * @param \Closure(): T $operation
     *
     * @return T
     */
    public function transactional(\Closure $operation): mixed
    {
        return $this->entityManager->wrapInTransaction(static fn (): mixed => $operation());
    }
}
