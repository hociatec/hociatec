<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\BetaTest\Service;

use App\Infrastructure\Persistence\DoctrineUnitOfWork;
use App\Module\BetaTest\Domain\Entity\BetaTesterProfile;

final readonly class ChangeBetaTesterStatusHandler
{
    public function __construct(private DoctrineUnitOfWork $persistence)
    {
    }

    public function change(BetaTesterProfile $profile, string $status): void
    {
        if (!in_array($status, ['pending', 'accepted', 'paused', 'rejected'], true)) {
            throw new \InvalidArgumentException('État invalide.');
        }

        $profile->setStatus($status);
        $this->persistence->flush();
    }
}
