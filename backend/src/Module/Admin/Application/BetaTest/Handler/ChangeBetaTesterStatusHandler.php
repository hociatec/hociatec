<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\BetaTest\Handler;

use App\Module\BetaTest\Domain\Entity\BetaTesterProfile;
use App\Module\BetaTest\Domain\Enum\BetaTesterStatus;
use App\Shared\Application\UnitOfWork;

final readonly class ChangeBetaTesterStatusHandler
{
    public function __construct(private UnitOfWork $persistence)
    {
    }

    public function change(BetaTesterProfile $profile, string $status): void
    {
        if (null === BetaTesterStatus::tryFrom($status)) {
            throw new \InvalidArgumentException('État invalide.');
        }

        $profile->setStatus(BetaTesterStatus::from($status));
        $this->persistence->commit();
    }
}
