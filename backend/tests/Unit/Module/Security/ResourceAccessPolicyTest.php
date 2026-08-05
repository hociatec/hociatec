<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Security;

use App\Module\Audit\Domain\Entity\AuditRequest;
use App\Module\Audit\Domain\Entity\AuditType;
use App\Module\Audit\Domain\Security\AuditAccessPolicy;
use App\Module\BetaTest\Domain\Entity\BugReport;
use App\Module\BetaTest\Domain\Security\BugReportAccessPolicy;
use App\Module\Order\Domain\Entity\Order;
use App\Module\Order\Domain\Security\OrderAccessPolicy;
use App\Module\Quote\Domain\Entity\Quote;
use App\Module\Quote\Domain\Security\QuoteAccessPolicy;
use App\Module\TradeIn\Domain\Entity\TradeInRequest;
use App\Module\TradeIn\Domain\Enum\TradeInStatus;
use App\Module\TradeIn\Domain\Security\TradeInAccessPolicy;
use App\Module\User\Domain\Entity\User;
use PHPUnit\Framework\TestCase;

final class ResourceAccessPolicyTest extends TestCase
{
    public function testCustomerCannotReadAnotherCustomerOrder(): void
    {
        $owner = $this->user('order-owner@example.test');
        $other = $this->user('order-other@example.test');
        $order = new Order('ORD-IDOR-1', $owner);
        $policy = new OrderAccessPolicy();

        self::assertTrue($policy->canView($owner, $order));
        self::assertFalse($policy->canView($other, $order));
    }

    public function testCustomerCannotDownloadAnotherCustomerOrderInvoice(): void
    {
        $owner = $this->user('invoice-owner@example.test');
        $other = $this->user('invoice-other@example.test');
        $order = (new Order('ORD-IDOR-PDF', $owner))
            ->setStatus(Order::STATUS_CONFIRMED)
            ->setInvoiceStatus(Order::INVOICE_STATUS_ISSUED);
        $policy = new OrderAccessPolicy();

        self::assertTrue($policy->canDownloadInvoice($owner, $order));
        self::assertFalse($policy->canDownloadInvoice($other, $order));
    }

    public function testCustomerCannotModifyAnotherCustomerQuote(): void
    {
        $owner = $this->user('quote-owner@example.test');
        $other = $this->user('quote-other@example.test');
        $quote = (new Quote('QUO-IDOR-1'))
            ->setCustomerEmail($owner->getEmail())
            ->setStatus(Quote::STATUS_SENT);
        $policy = new QuoteAccessPolicy();

        self::assertTrue($policy->canView($owner, $quote));
        self::assertFalse($policy->canView($other, $quote));
    }

    public function testCustomerCannotReadAnotherCustomerAuditReport(): void
    {
        $owner = $this->user('audit-owner@example.test');
        $other = $this->user('audit-other@example.test');
        $audit = new AuditRequest('AUD-IDOR-1', $owner, AuditType::SEO, 'https://site.test', null);
        $policy = new AuditAccessPolicy();

        self::assertTrue($policy->canView($owner, $audit));
        self::assertFalse($policy->canView($other, $audit));
    }

    public function testCustomerCannotCommentOrDownloadAnotherCustomerBugReport(): void
    {
        $owner = $this->user('bug-owner@example.test');
        $other = $this->user('bug-other@example.test');
        $report = new BugReport($owner, null, 'Bug', 'Description', null, null, 'medium', null, ['screen.png']);
        $policy = new BugReportAccessPolicy();

        self::assertTrue($policy->canView($owner, $report));
        self::assertTrue($policy->canComment($owner, $report));
        self::assertTrue($policy->canDownloadAttachment($owner, $report));
        self::assertFalse($policy->canView($other, $report));
        self::assertFalse($policy->canComment($other, $report));
        self::assertFalse($policy->canDownloadAttachment($other, $report));
    }

    public function testCustomerCannotDownloadAnotherCustomerTradeInReceipt(): void
    {
        $owner = $this->user('trade-owner@example.test');
        $other = $this->user('trade-other@example.test');
        $request = $this->tradeInRequest($owner);
        $request->setStatus(TradeInStatus::COMPLETED);
        $request->setReceiptPath('private/receipts/TR-IDOR.pdf');
        $policy = new TradeInAccessPolicy();

        self::assertTrue($policy->canDownloadReceipt($owner, $request));
        self::assertFalse($policy->canDownloadReceipt($other, $request));
    }

    public function testAdminRoleIsNotImplicitlyGrantedByDomainPolicies(): void
    {
        $owner = $this->user('owner@example.test');
        $admin = $this->user('admin@example.test')->setRoles(['ROLE_ADMIN']);

        self::assertFalse((new AuditAccessPolicy())->canView(
            $admin,
            new AuditRequest('AUD-IDOR-ADMIN', $owner, AuditType::SEO, 'https://site.test', null),
        ));
        self::assertFalse((new BugReportAccessPolicy())->canComment(
            $admin,
            new BugReport($owner, null, 'Bug', 'Description', null, null, 'medium', null),
        ));
        self::assertFalse((new TradeInAccessPolicy())->canDownloadReceipt(
            $admin,
            $this->tradeInRequest($owner)->setReceiptPath('private/receipts/TR-IDOR-ADMIN.pdf'),
        ));
    }

    public function testBusinessStateCanForbidAnActionEvenForTheOwner(): void
    {
        $owner = $this->user('state-owner@example.test');
        $cancelled = (new Order('ORD-STATE-CANCELLED', $owner))
            ->setStatus(Order::STATUS_CANCELLED)
            ->setDeliveryStatus(Order::DELIVERY_STATUS_PREPARING)
            ->setInvoiceStatus(Order::INVOICE_STATUS_ISSUED);
        $delivered = (new Order('ORD-STATE-DELIVERED', $owner))
            ->setStatus(Order::STATUS_DELIVERED)
            ->setDeliveryStatus(Order::DELIVERY_STATUS_DELIVERED)
            ->setInvoiceStatus(Order::INVOICE_STATUS_ISSUED);
        $policy = new OrderAccessPolicy();

        self::assertTrue($policy->canView($owner, $cancelled));
        self::assertFalse($policy->canDownloadInvoice($owner, $cancelled));
        self::assertFalse($policy->canCancel($owner, $delivered));
    }

    private function user(string $email): User
    {
        return new User($email, 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
    }

    private function tradeInRequest(User $user): TradeInRequest
    {
        return new TradeInRequest(
            'TR-IDOR',
            $user,
            'Ada',
            'Lovelace',
            $user->getEmail(),
            '0102030405',
            'Laptop',
            'ThinkPad',
            100000,
            2023,
            'Lenovo',
            'X1',
            null,
            'good',
            true,
            true,
            true,
            'Good state',
            null,
            null,
            50000,
            70000,
            new \DateTimeImmutable(),
        );
    }
}
