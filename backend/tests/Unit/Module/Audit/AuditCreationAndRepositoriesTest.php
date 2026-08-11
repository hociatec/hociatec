<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Audit;

use App\Module\Audit\Application\Provider\AuditTemplateProvider;
use App\Module\Audit\Application\Workflow\CreateAuditRequestService;
use App\Module\Audit\Domain\Entity\AuditChecklistItem;
use App\Module\Audit\Domain\Entity\AuditEvent;
use App\Module\Audit\Domain\Entity\AuditType;
use App\Shared\Infrastructure\Doctrine\DoctrineUnitOfWork;

final class AuditCreationAndRepositoriesTest extends AuditIntegrationTestCase
{
    public function testCreateServicePersistsTemplatedAuditAndRepositoriesQueryIt(): void
    {
        $user = $this->persistUser('audit-owner@example.test');
        $service = new CreateAuditRequestService(new DoctrineUnitOfWork($this->entityManager()), new AuditTemplateProvider());

        $audit = $service->create($user, AuditType::ACCESSIBILITY, 'https://example.test', "Line 1\nLine 2");
        $event = new AuditEvent($audit, 'created', 'Created', $user->getId(), 'Ada Lovelace');
        $this->entityManager()->persist($event);
        $this->entityManager()->flush();

        self::assertNotNull($audit->getId());
        self::assertMatchesRegularExpression('/^AUD-\d{8}-[A-F0-9]{4}$/', $audit->getNumber());
        self::assertGreaterThan(0, $audit->getItems()->count());

        $audits = $this->auditRequests()->findByUser($user);
        self::assertSame([$audit], $audits);
        self::assertSame($audit, $this->auditRequests()->find($audit->getId()));
        self::assertSame([$event], $this->auditEvents()->findByAudit($audit, 'ASC'));
        self::assertSame([$event], $this->auditEvents()->findByAudit($audit, 'bad-order'));

        $firstItem = $audit->getItems()->first();
        self::assertInstanceOf(AuditChecklistItem::class, $firstItem);
        self::assertSame($firstItem, $this->auditChecklistItems()->find($firstItem->getId()));
    }
}
