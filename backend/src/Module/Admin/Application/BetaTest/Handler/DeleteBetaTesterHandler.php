<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\BetaTest\Handler;

use App\Module\BetaTest\Domain\Entity\BetaTesterProfile;
use App\Shared\Application\UnitOfWork;

final readonly class DeleteBetaTesterHandler
{
    public function __construct(private UnitOfWork $persistence)
    {
    }

    public function delete(BetaTesterProfile $profile): void
    {
        $this->persistence->remove($profile);
        $this->persistence->flush();
    }
}
