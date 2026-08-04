<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Misc;

use App\Module\Admin\Appointment\DTO\PrestationInput;
use App\Module\Admin\Appointment\DTO\WorkingDaysInput;
use App\Module\Admin\Audit\DTO\AuditStatusInput;
use App\Module\Admin\Audit\DTO\ChecklistItemInput;
use App\Module\Admin\Catalog\DTO\CatalogNameInput;
use App\Module\Admin\Catalog\DTO\CategoryInput;
use App\Module\Admin\DTO\BackupSettingsInput;
use App\Module\Admin\DTO\MaintenanceInput;
use App\Module\Admin\Voucher\DTO\VoucherInput;
use App\Module\Auth\DTO\RequestPasswordResetInput;
use App\Module\Auth\DTO\ResetPasswordInput;
use App\Module\Audit\Entity\AuditRequest;
use App\Module\Cart\DTO\AddCartItemInput;
use App\Module\Cart\DTO\ApplyCartVoucherInput;
use App\Module\Cart\DTO\UpdateCartItemInput;
use App\Module\Catalog\DTO\ProductSearchCriteria;
use App\Module\Catalog\DTO\ShareProductInput;
use App\Module\Contact\DTO\ContactInput;
use App\Module\Order\DTO\CheckoutInput;
use App\Module\Order\Enum\DeliveryStatus;
use App\Module\Order\Enum\OrderStatus;
use App\Module\Order\Enum\RefundStatus;
use App\Module\Support\Enum\SupportStatus;
use App\Module\TradeIn\Enum\TradeInStatus;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

final class DtoAndEnumCoverageTest extends TestCase
{
    public function testAdminAndCartDtosFromArray(): void
    {
        $constructedCategory = new CategoryInput('Direct', null, 'direct', false);
        self::assertSame('Direct', $constructedCategory->name);
        self::assertFalse($constructedCategory->isVisible);

        $prestation = PrestationInput::fromArray(['name' => '  Audit ', 'durationMinutes' => '45', 'price' => '99.99']);
        self::assertSame('Audit', $prestation->name);
        self::assertSame(45, $prestation->durationMinutes);
        self::assertSame('99.99', $prestation->price);

        $days = WorkingDaysInput::fromArray(['days' => [['day' => 'mon'], 'x', ['day' => 'tue']]]);
        self::assertSame([['day' => 'mon'], ['day' => 'tue']], $days->days);
        self::assertSame(['days' => [['day' => 'mon'], ['day' => 'tue']]], $days->toPayload());

        $auditStatus = AuditStatusInput::fromArray(['status' => ' '.AuditRequest::STATUS_REVIEW.' ']);
        self::assertSame(AuditRequest::STATUS_REVIEW, $auditStatus->status);

        $checklist = ChecklistItemInput::fromArray(['isCompliant' => true, 'comment' => ' ok ']);
        self::assertTrue($checklist->isCompliant);
        self::assertSame('ok', $checklist->comment);

        $catalogName = CatalogNameInput::fromArray(['name' => '  Phones ']);
        self::assertSame('Phones', $catalogName->name);

        $category = CategoryInput::fromArray(['name' => ' Cat ', 'description' => ' Desc ', 'slug' => ' ', 'isVisible' => 'yes']);
        self::assertSame('Cat', $category->name);
        self::assertSame('Desc', $category->description);
        self::assertNull($category->slug);
        self::assertTrue($category->isVisible);

        $categoryHidden = CategoryInput::fromArray(['name' => 'Hidden', 'isVisible' => 'no']);
        self::assertFalse($categoryHidden->isVisible);

        $categoryVisible = CategoryInput::fromArray(['name' => 'Visible', 'isVisible' => true]);
        self::assertTrue($categoryVisible->isVisible);

        $categoryNumeric = CategoryInput::fromArray(['name' => 'Numeric', 'isVisible' => 0]);
        self::assertFalse($categoryNumeric->isVisible);

        $backup = BackupSettingsInput::fromArray(['enabled' => true, 'intervalHours' => '24', 'retentionCount' => 7, 'message' => ' msg ']);
        self::assertSame(['enabled' => true, 'intervalHours' => 24, 'retentionCount' => 7], $backup->settings());
        self::assertTrue($backup->maintenanceEnabled);
        self::assertSame('msg', $backup->message);

        $maintenance = MaintenanceInput::fromArray(['enabled' => true, 'message' => ' down ']);
        self::assertTrue($maintenance->enabled);
        self::assertSame('down', $maintenance->message);

        $voucher = VoucherInput::fromArray([
            'name' => ' Gift ',
            'code' => ' gift10 ',
            'description' => ' Desc ',
            'discountType' => 'percent',
            'discountValue' => '15',
            'isActive' => false,
            'startsAt' => ' 2026-07-01 ',
            'endsAt' => ' 2026-08-01 ',
        ]);
        self::assertSame('Gift', $voucher->name);
        self::assertSame('GIFT10', $voucher->code);
        self::assertFalse($voucher->isActive);

        $add = AddCartItemInput::fromArray(['productId' => '88', 'quantity' => '2', 'rentalMonths' => '3']);
        self::assertSame(88, $add->productId);
        self::assertSame(2, $add->quantity);
        self::assertSame(3, $add->rentalMonths);

        $apply = ApplyCartVoucherInput::fromArray(['voucherCode' => ' GIFT10 ']);
        self::assertSame('GIFT10', $apply->voucherCode);

        $update = UpdateCartItemInput::fromArray(['quantity' => '0', 'rentalMonths' => '6', 'currentRentalMonths' => '3']);
        self::assertSame(0, $update->quantity);
        self::assertSame(6, $update->rentalMonths);
        self::assertSame(3, $update->currentRentalMonths);
    }

    public function testAuthCatalogContactDtosAndEnums(): void
    {
        $requestReset = RequestPasswordResetInput::fromArray(['email' => '  Ada@Example.COM ']);
        self::assertSame('ada@example.com', $requestReset->email);

        $reset = ResetPasswordInput::fromArray(['password' => 'Password1', 'confirmPassword' => 'Mismatch1']);
        self::assertSame('Password1', $reset->password);
        self::assertSame('Mismatch1', $reset->confirmPassword);

        $builder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $builder->expects(self::once())->method('atPath')->with('confirmPassword')->willReturnSelf();
        $builder->expects(self::once())->method('addViolation');

        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects(self::once())->method('buildViolation')->with('Les mots de passe doivent être identiques.')->willReturn($builder);
        $reset->validatePasswords($context);

        $checkout = CheckoutInput::fromArray(['addressId' => '12']);
        self::assertSame(12, $checkout->addressId);

        $criteria = new ProductSearchCriteria(3, 20, 'phones', 'iphone', true, 'sale', 'Apple', '256Go', '8Go', 'Black', 10000, 20000, true, 'price_desc');
        self::assertSame(40, $criteria->offset());
        self::assertSame(['phones', 'iphone', true, 'sale', 'Apple', '256Go', '8Go', 'Black', 10000, 20000, true], $criteria->filterArguments());

        $share = ShareProductInput::fromPayload(['email' => '  Ada@Example.COM ']);
        self::assertSame('ada@example.com', $share->email);

        $shareInvalid = ShareProductInput::fromPayload('invalid');
        self::assertSame('', $shareInvalid->email);

        $contact = ContactInput::fromArray(['name' => ' Ada ', 'email' => ' ADA@EXAMPLE.COM ', 'subject' => ' Hello ', 'message' => ' Test ']);
        self::assertSame('Ada', $contact->name);
        self::assertSame('ada@example.com', $contact->email);
        self::assertSame('Hello', $contact->subject);
        self::assertSame('Test', $contact->message);

        self::assertSame('pending', OrderStatus::PENDING->value);
        self::assertSame('shipped', DeliveryStatus::SHIPPED->value);
        self::assertSame('approved', RefundStatus::APPROVED->value);
        self::assertSame('resolved', SupportStatus::RESOLVED->value);
        self::assertSame('completed', TradeInStatus::COMPLETED->value);
    }
}
