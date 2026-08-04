<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Security;

use App\Module\Audit\Domain\Entity\AuditRequest;
use App\Module\Audit\Domain\Entity\AuditType;
use App\Module\Audit\Domain\Security\AuditAccessPolicy;
use App\Module\BetaTest\Domain\Entity\BugReport;
use App\Module\BetaTest\Domain\Security\BugReportAccessPolicy;
use App\Module\TradeIn\Domain\Entity\TradeInRequest;
use App\Module\TradeIn\Domain\Enum\TradeInStatus;
use App\Module\TradeIn\Domain\Security\TradeInAccessPolicy;
use App\Module\User\Domain\Entity\User;
use PHPUnit\Framework\TestCase;

final class ResourceAccessPolicyTest extends TestCase
{
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
