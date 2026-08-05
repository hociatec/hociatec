<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Doctrine;

use App\Shared\Application\TransactionManager;
use App\Shared\Application\TransactionSideEffectRegistry;
use App\Shared\Infrastructure\Transaction\InMemoryTransactionSideEffectRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\NullLogger;

final class DoctrineTransactionManager implements TransactionManager
{
    private TransactionSideEffectRegistry $sideEffects;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        ?TransactionSideEffectRegistry $sideEffects = null,
    ) {
        $this->sideEffects = $sideEffects ?? new InMemoryTransactionSideEffectRegistry(new NullLogger());
    }

    public function transactional(\Closure $operation): mixed
    {
        $this->sideEffects->begin();

        try {
            $result = $this->entityManager->wrapInTransaction(static fn (): mixed => $operation());
            $this->sideEffects->commit();

            return $result;
        } catch (\Throwable $exception) {
            $this->sideEffects->rollback();

            throw $exception;
        }
    }
}
