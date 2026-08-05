<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\BetaTest\Handler;

use App\Module\BetaTest\Domain\Entity\BetaTesterProfile;
use App\Shared\Infrastructure\Doctrine\DoctrineUnitOfWork;

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
