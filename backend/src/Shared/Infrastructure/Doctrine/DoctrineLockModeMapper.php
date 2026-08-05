<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Doctrine;

use App\Shared\Application\LockMode as ApplicationLockMode;
use Doctrine\DBAL\LockMode as DoctrineLockMode;

final class DoctrineLockModeMapper
{
    private function __construct()
    {
    }

    public static function toDoctrine(ApplicationLockMode|DoctrineLockMode|int|null $lockMode): DoctrineLockMode|int|null
    {
        return match ($lockMode) {
            ApplicationLockMode::NONE => DoctrineLockMode::NONE,
            ApplicationLockMode::OPTIMISTIC => DoctrineLockMode::OPTIMISTIC,
            ApplicationLockMode::PESSIMISTIC_READ => DoctrineLockMode::PESSIMISTIC_READ,
            ApplicationLockMode::PESSIMISTIC_WRITE => DoctrineLockMode::PESSIMISTIC_WRITE,
            default => $lockMode,
        };
    }
}
