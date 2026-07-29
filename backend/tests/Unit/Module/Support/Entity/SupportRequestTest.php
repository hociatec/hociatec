<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Support\Entity;

use App\Module\Order\Entity\Order;
use App\Module\Support\Entity\SupportRequest;
use App\Module\User\Entity\User;
use PHPUnit\Framework\TestCase;

final class SupportRequestTest extends TestCase
{
    public function testSupportRequestLifecycleAndMutators(): void
    {
        $user = new User('ada@example.com', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $order = new Order('ORD-1', $user);
        $support = new SupportRequest($user, '   ');
        $updatedAt = $support->getUpdatedAt();

        self::assertNull($support->getId());
        self::assertSame($user, $support->getCustomer());
        self::assertNull($support->getOrder());
        self::assertSame(SupportRequest::STATUS_NEW, $support->getStatus());
        self::assertSame('other', $support->getReason());
        self::assertSame('Demande SAV', $support->getSubject());
        self::assertNull($support->getMessage());
        self::assertNull($support->getInternalNotes());
        self::assertSame([], $support->getAttachments());
        self::assertInstanceOf(\DateTimeImmutable::class, $support->getCreatedAt());
        self::assertNull($support->getResolvedAt());

        $support
            ->setOrder($order)
            ->setStatus(SupportRequest::STATUS_IN_PROGRESS)
            ->setReason('  shipping ')
            ->setSubject('  Produit recu ')
            ->setMessage('  message ')
            ->setInternalNotes('  notes ')
            ->setAttachments([['name' => 'doc.pdf']]);

        self::assertSame($order, $support->getOrder());
        self::assertSame(SupportRequest::STATUS_IN_PROGRESS, $support->getStatus());
        self::assertSame('shipping', $support->getReason());
        self::assertSame('Produit recu', $support->getSubject());
        self::assertSame('message', $support->getMessage());
        self::assertSame('notes', $support->getInternalNotes());
        self::assertSame([['name' => 'doc.pdf']], $support->getAttachments());
        self::assertNull($support->getResolvedAt());

        $support->setStatus(SupportRequest::STATUS_RESOLVED);
        self::assertInstanceOf(\DateTimeImmutable::class, $support->getResolvedAt());
        $resolvedAt = $support->getResolvedAt();

        $support->setStatus(SupportRequest::STATUS_REFUSED);
        self::assertSame($resolvedAt, $support->getResolvedAt());

        $support->setReason('   ')->setSubject('   ')->setMessage(null)->setInternalNotes(null);
        self::assertSame('other', $support->getReason());
        self::assertSame('Produit recu', $support->getSubject());
        self::assertNull($support->getMessage());
        self::assertNull($support->getInternalNotes());

        usleep(1000);
        $support->touch();
        self::assertGreaterThanOrEqual($updatedAt, $support->getUpdatedAt());
    }
}
