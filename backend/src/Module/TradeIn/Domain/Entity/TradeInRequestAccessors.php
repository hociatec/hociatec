<?php

declare(strict_types=1);

namespace App\Module\TradeIn\Domain\Entity;

trait TradeInRequestAccessors
{
    use TradeInRequestLifecycleAccessorsTrait;
    use TradeInRequestProductAccessorsTrait;
    use TradeInRequestSettlementAccessorsTrait;
    use TradeInRequestSubmissionTrait;
}
