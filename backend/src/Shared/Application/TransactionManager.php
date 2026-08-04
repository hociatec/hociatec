<?php

declare(strict_types=1);

namespace App\Shared\Application;

interface TransactionManager
{
    /**
     * @template T
     *
     * @param \Closure(): T $operation
     *
     * @return T
     */
    public function transactional(\Closure $operation): mixed;
}
