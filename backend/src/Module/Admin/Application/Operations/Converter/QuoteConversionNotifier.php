<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Operations\Converter;

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
            return [$this->orderServices->notifications->sendOrderCreatedIfNeeded($order), null];
        } catch (\RuntimeException $exception) {
            $this->orderServices->events->log($order, null, 'email_failed', 'Échec email commande à régler: '.$exception->getMessage());

            return [false, 'La notification email n’a pas pu être envoyée.'];
        }
    }
}
