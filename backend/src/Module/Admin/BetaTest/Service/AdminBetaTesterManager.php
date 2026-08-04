<?php

declare(strict_types=1);

namespace App\Module\Admin\BetaTest\Service;

use App\Module\BetaTest\Entity\BetaTesterProfile;
use App\Shared\Persistence\DoctrinePersistence;

final readonly class AdminBetaTesterManager
{
    public function __construct(private DoctrinePersistence $persistence)
    {
    }

    public function updateStatus(BetaTesterProfile $profile, string $status): void
    {
        if (!in_array($status, ['pending', 'accepted', 'paused', 'rejected'], true)) {
            throw new \InvalidArgumentException('État invalide.');
        }

        $profile->setStatus($status);
        $this->persistence->flush();
    }

    public function delete(BetaTesterProfile $profile): void
    {
        $this->persistence->remove($profile);
        $this->persistence->flush();
    }
}
