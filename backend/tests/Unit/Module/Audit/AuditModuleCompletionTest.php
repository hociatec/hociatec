<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Audit;

use App\Module\Admin\Audit\Controller\GeneratePdfController as AdminGeneratePdfController;
use App\Module\Admin\Audit\Controller\ListAuditsController as AdminListAuditsController;
use App\Module\Admin\Audit\Controller\ShowAuditController as AdminShowAuditController;
use App\Module\Admin\Audit\Controller\UpdateAuditStatusController as AdminUpdateAuditStatusController;
use App\Module\Admin\Audit\Controller\UpdateChecklistItemController as AdminUpdateChecklistItemController;
use App\Module\Audit\Controller\Client\CreateAuditController;
use App\Module\Audit\Controller\Client\GeneratePdfController;
use App\Module\Audit\Controller\Client\ListMyAuditsController;
use App\Module\Audit\Controller\Client\ShowMyAuditController;
use App\Module\Audit\Entity\AuditChecklistItem;
use App\Module\Audit\Entity\AuditEvent;
use App\Module\Audit\Entity\AuditRequest;
use App\Module\Audit\Entity\AuditType;
use App\Module\Audit\Repository\AuditChecklistItemRepository;
use App\Module\Audit\Repository\AuditEventRepository;
use App\Module\Audit\Repository\AuditRequestRepository;
use App\Module\Audit\Service\AuditEventLogger;
use App\Module\Audit\Service\AuditMetadataFormatter;
use App\Module\Audit\Service\AuditPdfService;
use App\Module\Audit\Service\AuditPersistence;
use App\Module\Audit\Service\AuditTemplateProvider;
use App\Module\Audit\Service\CreateAuditRequestService;
use App\Module\User\Entity\User;
use App\Shared\Persistence\DoctrinePersistence;
use App\Shared\Validation\ConstraintViolationFormatter;
use App\Shared\Validation\DtoValidator;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Validator\Validation;

final class AuditModuleCompletionTest extends TestCase
{
    private ?EntityManager $entityManager = null;

    protected function tearDown(): void
    {
        $this->entityManager?->close();
        $this->entityManager = null;
    }

    public function testCreateServicePersistsTemplatedAuditAndRepositoriesQueryIt(): void
    {
        $user = $this->persistUser('audit-owner@example.test');
        $service = new CreateAuditRequestService(new AuditPersistence($this->entityManager()), new AuditTemplateProvider());

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

        $show = new ShowMyAuditController($this->auditRequests(), $this->auditEvents(), $metadata);
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

        $pdf = new GeneratePdfController($this->auditRequests(), new AuditPdfService(), $this->eventLogger());
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
        }, $this->eventLogger());
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

        $pdf = new AdminGeneratePdfController($this->auditRequests(), new AuditPdfService(), $this->eventLogger());
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
        }, $this->eventLogger());
        $unavailablePdf->setContainer($this->container($admin));
        self::assertSame(501, $unavailablePdf->detailed((int) $audit->getId())->getStatusCode());
        self::assertSame(501, $unavailablePdf->summary((int) $audit->getId())->getStatusCode());
    }

    private function persistUser(string $email): User
    {
        $user = new User($email, 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $user->setPassword('hashed');
        $this->entityManager()->persist($user);
        $this->entityManager()->flush();

        return $user;
    }

    private function entityManager(): EntityManager
    {
        if ($this->entityManager instanceof EntityManager) {
            return $this->entityManager;
        }

        $config = ORMSetup::createAttributeMetadataConfiguration([__DIR__.'/../../../src'], true);
        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true], $config);
        $entityManager = new EntityManager($connection, $config);
        (new SchemaTool($entityManager))->createSchema([
            $entityManager->getClassMetadata(User::class),
            $entityManager->getClassMetadata(AuditRequest::class),
            $entityManager->getClassMetadata(AuditChecklistItem::class),
            $entityManager->getClassMetadata(AuditEvent::class),
        ]);

        return $this->entityManager = $entityManager;
    }

    private function registry(): ManagerRegistry
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($this->entityManager());

        return $registry;
    }

    private function auditRequests(): AuditRequestRepository
    {
        return new AuditRequestRepository($this->registry());
    }

    private function auditEvents(): AuditEventRepository
    {
        return new AuditEventRepository($this->registry());
    }

    private function auditChecklistItems(): AuditChecklistItemRepository
    {
        return new AuditChecklistItemRepository($this->registry());
    }

    private function eventLogger(): AuditEventLogger
    {
        return new AuditEventLogger(new DoctrinePersistence($this->entityManager()));
    }

    private function validator(): DtoValidator
    {
        return new DtoValidator(Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator(), new ConstraintViolationFormatter());
    }

    /** @param array<string,mixed> $payload */
    private function jsonRequest(array $payload): Request
    {
        return Request::create('/', 'POST', server: ['CONTENT_TYPE' => 'application/json'], content: json_encode($payload, JSON_THROW_ON_ERROR));
    }

    /** @return array<string,mixed> */
    private function payload(Response $response): array
    {
        return json_decode($response->getContent() ?: '{}', true, 512, JSON_THROW_ON_ERROR);
    }

    private function container(User $user): Container
    {
        $tokenStorage = new TokenStorage();
        $tokenStorage->setToken(new UsernamePasswordToken($user, 'main', $user->getRoles()));

        $container = new Container();
        $container->set('security.token_storage', $tokenStorage);

        return $container;
    }
}
