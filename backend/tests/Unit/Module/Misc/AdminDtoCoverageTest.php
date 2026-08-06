<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Misc;

use App\Module\Admin\Application\Operations\DTO\BulkOrderStatusInput;
use App\Module\Admin\Application\Operations\DTO\RefundCreateInput;
use App\Module\Admin\Application\Operations\DTO\RefundProcessInput;
use App\Module\Admin\Application\Operations\DTO\RefundUpdateInput;
use App\Module\Admin\Application\Operations\DTO\StockMovementInput;
use App\Module\Admin\Application\Operations\DTO\SupportCreateInput;
use App\Module\Admin\Application\Operations\DTO\SupportReplyInput;
use App\Module\Admin\Application\Operations\DTO\SupportUpdateInput;
use App\Module\Admin\Application\Operations\DTO\UpdateLowStockThresholdInput;
use App\Module\Admin\Application\Order\DTO\OrderEmailScenarioInput;
use App\Module\Admin\Application\Order\DTO\OrderStatusInput;
use App\Module\TradeIn\Application\DTO\TradeInClosureInput;
use App\Module\Admin\Application\TradeIn\DTO\TradeInOfferInput;
use App\Module\Admin\Application\TradeIn\DTO\TradeInStatusInput;
use App\Module\Admin\Application\User\DTO\CustomerAdminProfileInput;
use App\Module\Admin\Application\User\DTO\CustomerEmailInput;
use App\Module\Admin\Application\User\DTO\CustomerVoucherInput;
use App\Module\Order\Domain\Entity\Order;
use App\Module\Order\Domain\Enum\RefundStatus;
use App\Module\Support\Domain\Enum\SupportStatus;
use App\Module\Voucher\Domain\Entity\Voucher;
use PHPUnit\Framework\TestCase;

final class AdminDtoCoverageTest extends TestCase
{
    public function testOperationsDtos(): void
    {
        $bulk = BulkOrderStatusInput::fromArray(['orderIds' => ['1', 'x', 2, 0], 'status' => ' '.Order::STATUS_CONFIRMED.' ']);
        self::assertSame([1, 2], $bulk->orderIds);
        self::assertSame(Order::STATUS_CONFIRMED, $bulk->status);

        $refundCreate = RefundCreateInput::fromArray([
            'orderId' => '10',
            'amountCents' => '2500',
            'reason' => ' reason ',
            'internalNotes' => ' notes ',
            'paymentId' => '30',
            'currencyCode' => ' usd ',
        ]);
        self::assertSame(10, $refundCreate->orderId);
        self::assertSame([
            'orderId' => 10,
            'amountCents' => 2500,
            'reason' => 'reason',
            'internalNotes' => 'notes',
            'paymentId' => 30,
            'currencyCode' => 'USD',
        ], $refundCreate->toPayload());

        $refundProcess = RefundProcessInput::fromArray(['confirmation' => ' REMBOURSER ', 'paymentIntentId' => ' pi_1 ']);
        self::assertSame(['confirmation' => 'REMBOURSER', 'paymentIntentId' => 'pi_1'], $refundProcess->toPayload());

        $refundUpdate = RefundUpdateInput::fromArray(['status' => ' '.RefundStatus::APPROVED->value.' ', 'stripeRefundId' => ' re_1 ', 'internalNotes' => ' note ']);
        self::assertSame([
            'status' => RefundStatus::APPROVED->value,
            'stripeRefundId' => 're_1',
            'internalNotes' => 'note',
        ], $refundUpdate->toPayload());

        $stock = StockMovementInput::fromArray(['productId' => '88', 'delta' => '-3', 'reason' => ' ', 'note' => ' note ']);
        self::assertSame(88, $stock->productId);
        self::assertSame(-3, $stock->delta);
        self::assertSame('adjustment', $stock->reason);
        self::assertSame('note', $stock->note);

        $supportCreate = SupportCreateInput::fromArray(['customerId' => '5', 'subject' => ' Subject ', 'reason' => ' damaged ', 'message' => ' msg ', 'internalNotes' => ' notes ', 'orderId' => '12']);
        self::assertSame([
            'customerId' => 5,
            'subject' => 'Subject',
            'reason' => 'damaged',
            'message' => 'msg',
            'internalNotes' => 'notes',
            'orderId' => 12,
        ], $supportCreate->toPayload());

        $supportReply = SupportReplyInput::fromArray(['message' => ' reply ', 'subject' => ' subj ', 'status' => ' '.SupportStatus::RESOLVED->value.' ']);
        self::assertSame([
            'message' => 'reply',
            'subject' => 'subj',
            'status' => SupportStatus::RESOLVED->value,
        ], $supportReply->toPayload());

        $supportUpdate = SupportUpdateInput::fromArray(['status' => ' '.SupportStatus::IN_PROGRESS->value.' ', 'internalNotes' => ' note ', 'subject' => ' subj ']);
        self::assertSame([
            'status' => SupportStatus::IN_PROGRESS->value,
            'internalNotes' => 'note',
            'subject' => 'subj',
        ], $supportUpdate->toPayload());

        $threshold = UpdateLowStockThresholdInput::fromArray(['threshold' => '0']);
        self::assertSame(0, $threshold->threshold);
    }

    public function testOrderTradeInAndUserDtos(): void
    {
        $emailScenario = OrderEmailScenarioInput::fromArray(['scenario' => ' current_status ']);
        self::assertSame('current_status', $emailScenario->scenario);

        $status = OrderStatusInput::fromArray(['status' => ' '.Order::STATUS_CANCELLED.' ']);
        self::assertSame(Order::STATUS_CANCELLED, $status->status);

        $closure = TradeInClosureInput::fromArray([
            'finalOfferCents' => '15000',
            'paymentMethod' => ' bank_transfer ',
            'paymentStatus' => ' paid ',
            'transactionReference' => ' tr-1 ',
            'note' => ' ok ',
        ]);
        self::assertSame(15000, $closure->finalOfferCents);
        self::assertSame('bank_transfer', $closure->paymentMethod);
        self::assertSame('paid', $closure->paymentStatus);
        self::assertSame('tr-1', $closure->transactionReference);
        self::assertSame('ok', $closure->note);

        $offer = TradeInOfferInput::fromArray(['offerCents' => '5000', 'adminNote' => ' note ', 'offerExpiresAt' => ' 2026-08-15 ']);
        self::assertSame(5000, $offer->offerCents);
        self::assertSame('note', $offer->adminNote);
        self::assertSame('2026-08-15', $offer->offerExpiresAt);

        $tradeStatus = TradeInStatusInput::fromArray(['status' => ' completed ']);
        self::assertSame('completed', $tradeStatus->status);

        $profile = CustomerAdminProfileInput::fromArray(['adminNotes' => ' note ', 'adminTags' => [' vip ', '', 'b2b']]);
        self::assertSame('note', $profile->adminNotes);
        self::assertSame(['vip', 'b2b'], $profile->adminTags);

        $email = CustomerEmailInput::fromArray(['subject' => ' Hello ', 'message' => ' World ']);
        self::assertSame('Hello', $email->subject);
        self::assertSame('World', $email->message);

        $voucher = CustomerVoucherInput::fromArray([
            'name' => ' Gift ',
            'code' => ' gift10 ',
            'description' => ' Desc ',
            'discountType' => Voucher::TYPE_PERCENT,
            'discountValue' => '12',
            'isActive' => false,
            'startsAt' => ' 2026-07-01 ',
            'endsAt' => ' 2026-08-01 ',
            'sendEmail' => false,
        ]);
        self::assertSame('Gift', $voucher->name);
        self::assertSame('GIFT10', $voucher->code);
        self::assertSame('Desc', $voucher->description);
        self::assertSame(Voucher::TYPE_PERCENT, $voucher->discountType);
        self::assertSame(12, $voucher->discountValue);
        self::assertFalse($voucher->isActive);
        self::assertFalse($voucher->sendEmail);
    }
}
