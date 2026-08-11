<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Audit;

use App\Module\Admin\UI\Audit\Controller\GeneratePdfController as AdminGeneratePdfController;
use App\Module\Admin\UI\Audit\Controller\ListAuditsController as AdminListAuditsController;
use App\Module\Admin\UI\Audit\Controller\ShowAuditController as AdminShowAuditController;
use App\Module\Admin\UI\Audit\Controller\UpdateAuditStatusController as AdminUpdateAuditStatusController;
use App\Module\Admin\UI\Audit\Controller\UpdateChecklistItemController as AdminUpdateChecklistItemController;
use App\Module\Audit\Application\Projection\AuditMetadataFormatter;
use App\Module\Audit\Domain\Entity\AuditChecklistItem;
use App\Module\Audit\Domain\Entity\AuditEvent;
use App\Module\Audit\Domain\Entity\AuditRequest;
use App\Module\Audit\Domain\Entity\AuditType;
use App\Module\Audit\Infrastructure\Pdf\AuditPdfService;
use Symfony\Component\HttpFoundation\Request;

final class AdminAuditControllersCompletionTest extends AuditIntegrationTestCase
{
    public function testAdminAuditControllersCoverListShowUpdatesAndPdf(): void
    {
        $admin = $this->persistUser('admin-audit@example.test');
        $client = $this->persistUser('admin-audit-client@example.test');
        $audit = new AuditRequest('AUD-ADMIN-1', $client, AuditType::ACCESSIBILITY, 'https://admin.test', 'Admin audit');
        $item = new AuditChecklistItem('Accessibilite', 'contrast', 'Contraste', 1);
        $audit->addItem($item);
        $otherAudit = new AuditRequest('AUD-ADMIN-2', $client, AuditType::SEO, 'https://other.test', null);
        $otherItem = new AuditChecklistItem('SEO', 'title', 'Title', 1);
        $otherAudit->addItem($otherItem);
        foreach ([$audit, $otherAudit, new AuditEvent($audit, 'created', 'Created', $admin->getId(), $admin->getFullName())] as $entity) {
            $this->entityManager()->persist($entity);
        }
        $this->entityManager()->flush();

        $metadata = new AuditMetadataFormatter();
        $list = new AdminListAuditsController($this->auditRequests(), $metadata);
        self::assertSame(200, $list(Request::create('/?page=1&perPage=10'))->getStatusCode());

        $show = new AdminShowAuditController($this->auditRequests(), $this->auditEvents(), $metadata);
        self::assertSame(404, $show(999)->getStatusCode());
        $showPayload = $this->payload($show((int) $audit->getId()));
        self::assertSame('AUD-ADMIN-1', $showPayload['data']['number']);

        $status = new AdminUpdateAuditStatusController($this->auditRequests(), $this->eventLogger(), $this->validator());
        $status->setContainer($this->container($admin));
        self::assertSame(404, $status(999, $this->jsonRequest(['status' => AuditRequest::STATUS_IN_PROGRESS]))->getStatusCode());
        self::assertSame(200, $status((int) $audit->getId(), $this->jsonRequest(['status' => AuditRequest::STATUS_DONE]))->getStatusCode());
        self::assertSame(AuditRequest::STATUS_DONE, $audit->getStatus());

        $checklist = new AdminUpdateChecklistItemController($this->auditRequests(), $this->auditChecklistItems(), $this->eventLogger(), $this->validator());
        $checklist->setContainer($this->container($admin));
        self::assertSame(404, $checklist(999, (int) $item->getId(), $this->jsonRequest(['isCompliant' => true]))->getStatusCode());
        self::assertSame(404, $checklist((int) $audit->getId(), 999, $this->jsonRequest(['isCompliant' => true]))->getStatusCode());
        self::assertSame(400, $checklist((int) $audit->getId(), (int) $otherItem->getId(), $this->jsonRequest(['isCompliant' => true]))->getStatusCode());
        self::assertSame(200, $checklist((int) $audit->getId(), (int) $item->getId(), $this->jsonRequest(['isCompliant' => true, 'comment' => 'OK']))->getStatusCode());
        self::assertSame(200, $checklist((int) $audit->getId(), (int) $item->getId(), $this->jsonRequest(['isCompliant' => true, 'comment' => 'OK']))->getStatusCode());

        $pdf = new AdminGeneratePdfController($this->auditRequests(), new AuditPdfService(), $this->eventLogger(), new \App\Shared\Infrastructure\Http\AttachmentResponseFactory());
        $pdf->setContainer($this->container($admin));
        self::assertSame(404, $pdf->detailed(999)->getStatusCode());
        self::assertSame(200, $pdf->detailed((int) $audit->getId())->getStatusCode());
        self::assertSame(404, $pdf->summary(999)->getStatusCode());
        self::assertSame(200, $pdf->summary((int) $audit->getId())->getStatusCode());

        $unavailablePdf = new AdminGeneratePdfController($this->auditRequests(), new class extends AuditPdfService {
            public function renderDetailed(AuditRequest $audit): string
            {
                throw new \RuntimeException('pdf unavailable');
            }

            public function renderSummary(AuditRequest $audit): string
            {
                throw new \RuntimeException('pdf unavailable');
            }
        }, $this->eventLogger(), new \App\Shared\Infrastructure\Http\AttachmentResponseFactory());
        $unavailablePdf->setContainer($this->container($admin));
        self::assertSame(501, $unavailablePdf->detailed((int) $audit->getId())->getStatusCode());
        self::assertSame(501, $unavailablePdf->summary((int) $audit->getId())->getStatusCode());
    }
}
