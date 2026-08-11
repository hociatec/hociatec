<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Audit;

use App\Module\Audit\Application\Projection\AuditMetadataFormatter;
use App\Module\Audit\Application\Provider\AuditTemplateProvider;
use App\Module\Audit\Application\Workflow\CreateAuditRequestService;
use App\Module\Audit\Domain\Entity\AuditChecklistItem;
use App\Module\Audit\Domain\Entity\AuditEvent;
use App\Module\Audit\Domain\Entity\AuditRequest;
use App\Module\Audit\Domain\Entity\AuditType;
use App\Module\Audit\Infrastructure\Pdf\AuditPdfService;
use App\Module\Audit\Infrastructure\Persistence\AuditPersistence;
use App\Module\Audit\UI\Controller\Client\CreateAuditController;
use App\Module\Audit\UI\Controller\Client\GeneratePdfController;
use App\Module\Audit\UI\Controller\Client\ListMyAuditsController;
use App\Module\Audit\UI\Controller\Client\ShowMyAuditController;

final class ClientAuditControllersTest extends AuditIntegrationTestCase
{
    public function testClientControllersCoverCreateListShowAndUnavailablePdf(): void
    {
        $user = $this->persistUser('client-audit@example.test');
        $audit = new AuditRequest('AUD-CLIENT-1', $user, AuditType::SEO, 'https://site.test', 'Improve');
        $item = (new AuditChecklistItem('SEO', 'title', 'Title tag', 1))->setIsCompliant(true);
        $audit->addItem($item);
        $this->entityManager()->persist($audit);
        $this->entityManager()->persist(new AuditEvent($audit, 'created', 'Created', $user->getId(), 'Ada Lovelace'));
        $this->entityManager()->flush();

        $metadata = new AuditMetadataFormatter();
        $list = new ListMyAuditsController($this->auditRequests(), $metadata);
        $list->setContainer($this->container($user));
        $listPayload = $this->payload($list());
        self::assertSame('AUD-CLIENT-1', $listPayload['data']['items'][0]['number']);

        $show = new ShowMyAuditController($this->auditRequests(), $this->auditEvents(), $metadata, new \App\Module\Audit\Domain\Security\AuditAccessPolicy());
        $show->setContainer($this->container($user));
        $showPayload = $this->payload($show((int) $audit->getId()));
        self::assertSame('title', $showPayload['data']['items'][0]['key']);
        self::assertSame('created', $showPayload['data']['events'][0]['type']);

        $other = $this->persistUser('other-audit@example.test');
        $show->setContainer($this->container($other));
        self::assertSame(404, $show((int) $audit->getId())->getStatusCode());

        $createService = new CreateAuditRequestService(new AuditPersistence($this->entityManager()), new AuditTemplateProvider());
        $create = new CreateAuditController($createService, $this->eventLogger(), $this->validator());
        $create->setContainer($this->container($user));
        self::assertSame(400, $create($this->jsonRequest(['type' => 'unknown', 'url' => 'https://site.test']))->getStatusCode());
        self::assertSame(201, $create($this->jsonRequest([
            'type' => AuditType::UX->value,
            'url' => ' https://ux.test ',
            'objectives' => 'Audit UX',
        ]))->getStatusCode());

        $pdf = new GeneratePdfController($this->auditRequests(), new AuditPdfService(), $this->eventLogger(), new \App\Shared\Infrastructure\Http\AttachmentResponseFactory(), new \App\Module\Audit\Domain\Security\AuditAccessPolicy());
        $pdf->setContainer($this->container($user));
        $detailedPdf = $pdf->detailed((int) $audit->getId());
        self::assertSame(200, $detailedPdf->getStatusCode());
        self::assertSame('application/pdf', $detailedPdf->headers->get('Content-Type'));

        $summaryPdf = $pdf->summary((int) $audit->getId());
        self::assertSame(200, $summaryPdf->getStatusCode());
        self::assertSame('application/pdf', $summaryPdf->headers->get('Content-Type'));
        self::assertSame(404, $pdf->summary(999)->getStatusCode());

        $unavailablePdf = new GeneratePdfController($this->auditRequests(), new class extends AuditPdfService {
            public function renderDetailed(AuditRequest $audit): string
            {
                throw new \RuntimeException('pdf unavailable');
            }

            public function renderSummary(AuditRequest $audit): string
            {
                throw new \RuntimeException('pdf unavailable');
            }
        }, $this->eventLogger(), new \App\Shared\Infrastructure\Http\AttachmentResponseFactory(), new \App\Module\Audit\Domain\Security\AuditAccessPolicy());
        $unavailablePdf->setContainer($this->container($user));
        self::assertSame(501, $unavailablePdf->detailed((int) $audit->getId())->getStatusCode());
        self::assertSame(501, $unavailablePdf->summary((int) $audit->getId())->getStatusCode());

        $logger = $this->eventLogger();
        $logger->save(new AuditEvent($audit, 'manual', null, null, null));
        self::assertContains('manual', array_map(
            static fn (AuditEvent $event): string => $event->getType(),
            $this->auditEvents()->findByAudit($audit, 'DESC'),
        ));
    }
}
