<?php

declare(strict_types=1);

namespace App\Module\TradeIn\Application\Workflow;

use App\Module\TradeIn\Domain\Enum\TradeInStatus;

final readonly class TradeInStatusWorkflow
{
    /** @return list<TradeInStatus> */
    public function nextStatuses(TradeInStatus $status): array
    {
        return match ($status) {
            TradeInStatus::SUBMITTED => [TradeInStatus::UNDER_REVIEW, TradeInStatus::CANCELLED, TradeInStatus::OFFER_SENT],
            TradeInStatus::UNDER_REVIEW => [TradeInStatus::OFFER_SENT, TradeInStatus::CANCELLED],
            TradeInStatus::OFFER_SENT => [TradeInStatus::ACCEPTED, TradeInStatus::DECLINED, TradeInStatus::EXPIRED, TradeInStatus::CANCELLED],
            TradeInStatus::ACCEPTED => [TradeInStatus::RECEIVED, TradeInStatus::CANCELLED],
            TradeInStatus::RECEIVED => [TradeInStatus::INSPECTED, TradeInStatus::CANCELLED],
            TradeInStatus::INSPECTED => [TradeInStatus::COMPLETED, TradeInStatus::CANCELLED],
            default => [],
        };
    }

    public function canTransitionTo(TradeInStatus $from, TradeInStatus $to): bool
    {
        return in_array($to, $this->nextStatuses($from), true);
    }
}
