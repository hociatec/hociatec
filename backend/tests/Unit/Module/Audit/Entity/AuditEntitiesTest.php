<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Audit\Entity;

use App\Module\Audit\Entity\AuditChecklistItem;
use App\Module\Audit\Entity\AuditEvent;
use App\Module\Audit\Entity\AuditRequest;
use App\Module\Audit\Entity\AuditType;
use App\Module\User\Entity\User;
use PHPUnit\Framework\TestCase;

final class AuditEntitiesTest extends TestCase
{
    public function testAuditRequestItemsLifecycleAndTouch(): void
    {
        $user = new User('ada@example.com', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $audit = new AuditRequest('AUD-1', $user, AuditType::ACCESSIBILITY, 'https://example.com', 'Goals');
        $initialUpdatedAt = $audit->getUpdatedAt();
        $item = new AuditChecklistItem('nav', '1.1', 'Navigation', 1);

        self::assertNull($audit->getId());
        self::assertSame('AUD-1', $audit->getNumber());
        self::assertSame($user, $audit->getClient());
        self::assertSame(AuditType::ACCESSIBILITY, $audit->getType());
        self::assertSame('https://example.com', $audit->getTargetUrl());
        self::assertSame('Goals', $audit->getObjectives());
        self::assertSame(AuditRequest::STATUS_NEW, $audit->getStatus());
        self::assertCount(0, $audit->getItems());
        self::assertInstanceOf(\DateTimeImmutable::class, $audit->getCreatedAt());

        $audit->setStatus(AuditRequest::STATUS_REVIEW)->addItem($item);
        self::assertSame(AuditRequest::STATUS_REVIEW, $audit->getStatus());
        self::assertCount(1, $audit->getItems());
        self::assertSame($audit, $item->getAudit());

        $audit->removeItem($item);
        self::assertCount(0, $audit->getItems());
        self::assertNull($item->getAudit());

        usleep(1000);
        $audit->touch();
        self::assertGreaterThanOrEqual($initialUpdatedAt, $audit->getUpdatedAt());
    }

    public function testAuditChecklistItemMutators(): void
    {
        $item = new AuditChecklistItem('forms', '2.1', 'Form labels', 2);

        self::assertNull($item->getId());
        self::assertSame('forms', $item->getCategory());
        self::assertSame('2.1', $item->getCriterionKey());
        self::assertSame('Form labels', $item->getLabel());
        self::assertSame(2, $item->getPosition());
        self::assertNull($item->getIsCompliant());
        self::assertNull($item->getComment());
        self::assertNull($item->getLevel());

        $item
            ->setIsCompliant(true)
            ->setComment('OK')
            ->setLevel('AA');

        self::assertTrue($item->getIsCompliant());
        self::assertSame('OK', $item->getComment());
        self::assertSame('AA', $item->getLevel());
    }

    public function testAuditEventExposesConstructorStateAndEnumCasesExist(): void
    {
        $user = new User('ada@example.com', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $audit = new AuditRequest('AUD-1', $user, AuditType::TECHNICAL, 'https://example.com', null);
        $event = new AuditEvent($audit, 'status_changed', 'Done', 42, 'Admin');

        self::assertNull($event->getId());
        self::assertSame($audit, $event->getAudit());
        self::assertSame('status_changed', $event->getType());
        self::assertSame('Done', $event->getMessage());
        self::assertSame(42, $event->getActorUserId());
        self::assertSame('Admin', $event->getActorName());
        self::assertInstanceOf(\DateTimeImmutable::class, $event->getCreatedAt());

        self::assertSame(
            ['performance', 'security', 'ux', 'seo', 'technical', 'accessibility'],
            array_map(static fn (AuditType $type): string => $type->value, AuditType::cases()),
        );
    }
}
