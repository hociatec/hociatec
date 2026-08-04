<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\BetaTest\Service;

use App\Infrastructure\Persistence\DoctrineUnitOfWork;
use App\Module\BetaTest\Domain\Entity\BetaTesterProfile;

final readonly class DeleteBetaTesterHandler
{
    public function __construct(private DoctrineUnitOfWork $persistence)
    {
    }

    public function delete(BetaTesterProfile $profile): void
    {
        $this->persistence->remove($profile);
        $this->persistence->commit();
    }
}
