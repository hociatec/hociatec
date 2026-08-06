<?php

declare(strict_types=1);

namespace App\Module\Quote\Application\Conversion;

use App\Module\Quote\Application\Conversion\DTO\QuoteConversionResult;
use App\Module\Order\Application\Projection\OrderFormatter;
use App\Module\Order\Domain\Entity\Order;
use App\Module\Quote\Domain\Entity\Quote;
use App\Module\User\Domain\Entity\User;

final readonly class QuoteConversionServices
{
    public function __construct(
        private OrderFormatter $orderFormatter,
        private QuoteConversionPolicy $policy,
        private QuoteOrderFactory $orderFactory,
        private QuoteConversionNotifier $notifier,
    ) {
    }

    public function assertConvertible(Quote $quote): void
    {
        $this->policy->assertConvertible($quote);
    }

    public function createOrder(Quote $quote, User $customer): Order
    {
        return $this->orderFactory->create($quote, $customer);
    }

    public function result(Order $order): QuoteConversionResult
    {
        [$emailSent, $emailError] = $this->notifier->sendOrderCreated($order);

        return new QuoteConversionResult($this->orderFormatter->formatOrder($order), $emailSent, $emailError);
    }
}
