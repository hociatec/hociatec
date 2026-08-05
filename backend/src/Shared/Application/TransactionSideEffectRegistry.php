<?php

declare(strict_types=1);

namespace App\Shared\Application;

interface TransactionSideEffectRegistry
{
    public function isTracking(): bool;

    public function begin(): void;

    /** @param \Closure(): void $effect */
    public function afterCommit(\Closure $effect): void;

    /** @param \Closure(): void $compensation */
    public function afterRollback(\Closure $compensation): void;

    public function commit(): void;

    public function rollback(): void;
}
