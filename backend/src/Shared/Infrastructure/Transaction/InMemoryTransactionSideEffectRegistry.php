<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Transaction;

use App\Shared\Application\TransactionSideEffectRegistry;
use Psr\Log\LoggerInterface;

final class InMemoryTransactionSideEffectRegistry implements TransactionSideEffectRegistry
{
    /**
     * @var list<array{afterCommit: list<\Closure(): void>, afterRollback: list<\Closure(): void>}>
     */
    private array $scopes = [];

    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    public function isTracking(): bool
    {
        return [] !== $this->scopes;
    }

    public function begin(): void
    {
        $this->scopes[] = ['afterCommit' => [], 'afterRollback' => []];
    }

    public function afterCommit(\Closure $effect): void
    {
        if (!$this->isTracking()) {
            $this->run($effect, 'Transaction side effect after commit failed.');

            return;
        }

        $index = count($this->scopes) - 1;
        $scope = $this->scopes[$index];
        $scope['afterCommit'][] = $effect;
        $this->scopes[$index] = $scope;
    }

    public function afterRollback(\Closure $compensation): void
    {
        if (!$this->isTracking()) {
            return;
        }

        $index = count($this->scopes) - 1;
        $scope = $this->scopes[$index];
        $scope['afterRollback'][] = $compensation;
        $this->scopes[$index] = $scope;
    }

    public function commit(): void
    {
        $scope = array_pop($this->scopes);
        if (null === $scope) {
            return;
        }

        if ($this->isTracking()) {
            $index = count($this->scopes) - 1;
            $parentScope = $this->scopes[$index];
            $parentScope['afterCommit'] = [
                ...$parentScope['afterCommit'],
                ...$scope['afterCommit'],
            ];
            $parentScope['afterRollback'] = [
                ...$parentScope['afterRollback'],
                ...$scope['afterRollback'],
            ];
            $this->scopes[$index] = $parentScope;

            return;
        }

        foreach ($scope['afterCommit'] as $effect) {
            $this->run($effect, 'Transaction side effect after commit failed.');
        }
    }

    public function rollback(): void
    {
        $scope = array_pop($this->scopes);
        if (null === $scope) {
            return;
        }

        foreach (array_reverse($scope['afterRollback']) as $compensation) {
            $this->run($compensation, 'Transaction side effect compensation failed.');
        }
    }

    /** @param \Closure(): void $callback */
    private function run(\Closure $callback, string $message): void
    {
        try {
            $callback();
        } catch (\Throwable $exception) {
            $this->logger->error($message, ['exception' => $exception]);
        }
    }
}
