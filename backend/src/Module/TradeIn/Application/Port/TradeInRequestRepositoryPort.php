<?php

declare(strict_types=1);

namespace App\Module\TradeIn\Application\Port;

use App\Module\TradeIn\Domain\Entity\TradeInRequest;
use App\Module\TradeIn\Domain\Enum\TradeInStatus;
use App\Module\User\Domain\Entity\User;
use App\Shared\Application\LockMode;

interface TradeInRequestRepositoryPort
{
    public function find(mixed $id, LockMode|int|null $lockMode = null, ?int $lockVersion = null): ?TradeInRequest;

    /** @return list<TradeInRequest> */
    public function findByUser(User $user): array;

    /** @return list<TradeInRequest> */
    public function findForAdmin(?string $search = null, ?TradeInStatus $status = null): array;

    public function delete(TradeInRequest $request): void;
}
