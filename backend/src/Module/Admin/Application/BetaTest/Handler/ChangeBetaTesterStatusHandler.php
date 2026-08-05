<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\BetaTest\Handler;

use App\Module\BetaTest\Domain\Entity\BetaTesterProfile;
use App\Shared\Application\UnitOfWork;

final readonly class ChangeBetaTesterStatusHandler
{
    public function __construct(private UnitOfWork $persistence)
    {
    }

    public function change(BetaTesterProfile $profile, string $status): void
    {
        if (!in_array($status, ['pending', 'accepted', 'paused', 'rejected'], true)) {
            throw new \InvalidArgumentException('État invalide.');
        }

        $profile->setStatus($status);
        $this->persistence->commit();
    }
}
