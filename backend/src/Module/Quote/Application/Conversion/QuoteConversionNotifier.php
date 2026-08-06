<?php

declare(strict_types=1);

namespace App\Module\Quote\Application\Conversion;

use App\Module\Order\Domain\Entity\Order;

final readonly class QuoteConversionNotifier
{
    public function __construct(private QuoteToOrderServices $orderServices)
    {
    }

    /** @return array{bool, ?string} */
    public function sendOrderCreated(Order $order): array
    {
        try {
            return [$this->orderServices->sendOrderCreatedNotification($order), null];
        } catch (\RuntimeException $exception) {
            $this->orderServices->logEmailFailure($order, $exception);

            return [false, 'La notification email n’a pas pu être envoyée.'];
        }
    }
}
