<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Audit\Controller;

use App\Module\Admin\UI\Audit\Controller\UpdateAuditStatusController;
use App\Module\Admin\UI\Audit\Controller\UpdateChecklistItemController;
use App\Module\Audit\Domain\Entity\AuditChecklistItem;
use App\Module\Audit\Domain\Entity\AuditRequest;
use App\Module\Audit\Domain\Entity\AuditType;
use App\Module\Audit\Infrastructure\Repository\AuditChecklistItemRepository;
use App\Module\Audit\Infrastructure\Repository\AuditRequestRepository;
use App\Module\Audit\Application\Service\AuditEventLogger;
use App\Module\User\Domain\Entity\User;
use App\Infrastructure\Validation\ConstraintViolationFormatter;
use App\Infrastructure\Validation\DtoValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validation;

final class AdminAuditWriteControllersTest extends TestCase
{
    public function testUpdateAuditStatusControllerHandlesNotFoundAndLogsStatusChange(): void
    {
        $audit = $this->audit();
        $actor = $this->actor();

        $repository = $this->getMockBuilder(AuditRequestRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['find'])
            ->getMock();
        $repository->expects(self::exactly(2))
            ->method('find')
            ->with(17)
            ->willReturnOnConsecutiveCalls(null, $audit);

        $events = $this->getMockBuilder(AuditEventLogger::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['save', 'log'])
            ->getMock();
        $events->expects(self::once())->method('save')->with($audit);
        $events->expects(self::once())
            ->method('log')
            ->with(
                $audit,
                $actor,
                'status_changed',
                'Statut: review → done',
            );

        $controller = new class($repository, $events, $this->validator(), $actor) extends UpdateAuditStatusController {
            public function __construct(
                AuditRequestRepository $repository,
                AuditEventLogger $events,
                DtoValidator $validator,
                private readonly ?User $actor,
            ) {
                parent::__construct($repository, $events, $validator);
            }

            public function getUser(): ?User
            {
                return $this->actor;
            }
        };

        $notFound = $controller->__invoke(17, $this->jsonRequest(['status' => 'done']));
        self::assertSame(404, $notFound->getStatusCode());
        self::assertSame('Audit introuvable.', $this->payload($notFound)['message']);

        $response = $controller->__invoke(17, $this->jsonRequest(['status' => 'done']));
        self::assertSame(200, $response->getStatusCode());
        self::assertSame(['status' => 'done'], $this->payload($response)['data']);
        self::assertSame(AuditRequest::STATUS_DONE, $audit->getStatus());
    }

    public function testUpdateChecklistItemControllerHandlesErrorsAndConditionalLogging(): void
    {
        $audit = $this->audit();
        $item = $audit->getItems()->first();
        self::assertInstanceOf(AuditChecklistItem::class, $item);
        $otherAudit = $this->otherAudit();
        $orphanItem = (new AuditChecklistItem('SEO', 'meta', 'Meta title', 2))->setAudit($otherAudit);
        $this->setId($orphanItem, 202);
        $actor = $this->actor();

        $audits = $this->getMockBuilder(AuditRequestRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['find'])
            ->getMock();
        $audits->expects(self::exactly(5))
            ->method('find')
            ->with(17)
            ->willReturnOnConsecutiveCalls(null, $audit, $audit, $audit, $audit);

        $items = $this->getMockBuilder(AuditChecklistItemRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['find'])
            ->getMock();
        $items->expects(self::exactly(4))
            ->method('find')
            ->with(201)
            ->willReturnOnConsecutiveCalls(null, $orphanItem, $item, $item);

        $events = $this->getMockBuilder(AuditEventLogger::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['save', 'log'])
            ->getMock();
        $events->expects(self::exactly(2))->method('save')->with($item);
        $events->expects(self::once())
            ->method('log')
            ->with(
                $audit,
                $actor,
                'item_updated',
                '[Performance] Chargement principal — Conformité: non → oui; Commentaire mis à jour',
            );

        $controller = new class($audits, $items, $events, $this->validator(), $actor) extends UpdateChecklistItemController {
            public function __construct(
                AuditRequestRepository $audits,
                AuditChecklistItemRepository $items,
                AuditEventLogger $events,
                DtoValidator $validator,
                private readonly ?User $actor,
            ) {
                parent::__construct($audits, $items, $events, $validator);
            }

            public function getUser(): ?User
            {
                return $this->actor;
            }
        };

        $notFoundAudit = $controller->__invoke(17, 201, $this->jsonRequest(['isCompliant' => true]));
        self::assertSame(404, $notFoundAudit->getStatusCode());
        self::assertSame('Audit introuvable.', $this->payload($notFoundAudit)['message']);

        $notFoundItem = $controller->__invoke(17, 201, $this->jsonRequest(['isCompliant' => true]));
        self::assertSame(404, $notFoundItem->getStatusCode());
        self::assertSame('Critère introuvable.', $this->payload($notFoundItem)['message']);

        $invalidAssociation = $controller->__invoke(17, 201, $this->jsonRequest(['isCompliant' => true]));
        self::assertSame(400, $invalidAssociation->getStatusCode());
        self::assertSame('Association invalide.', $this->payload($invalidAssociation)['message']);

        $response = $controller->__invoke(17, 201, $this->jsonRequest(['isCompliant' => true, 'comment' => ' Corrigé ']));
        self::assertSame(200, $response->getStatusCode());
        self::assertSame(['id' => 201, 'isCompliant' => true, 'comment' => 'Corrigé'], $this->payload($response)['data']);

        $secondResponse = $controller->__invoke(17, 201, $this->jsonRequest(['isCompliant' => true, 'comment' => 'Corrigé']));
        self::assertSame(200, $secondResponse->getStatusCode());
        self::assertSame(['id' => 201, 'isCompliant' => true, 'comment' => 'Corrigé'], $this->payload($secondResponse)['data']);
    }

    private function validator(): DtoValidator
    {
        return new DtoValidator(
            Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator(),
            new ConstraintViolationFormatter(),
        );
    }

    private function jsonRequest(array $payload): Request
    {
        return Request::create('/', 'PUT', server: ['CONTENT_TYPE' => 'application/json'], content: json_encode($payload, JSON_THROW_ON_ERROR));
    }

    private function payload(object $response): array
    {
        return json_decode($response->getContent() ?: '{}', true, 512, JSON_THROW_ON_ERROR);
    }

    private function actor(): User
    {
        $user = new User('admin@example.com', 'Admin', 'Root', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $this->setId($user, 99);

        return $user;
    }

    private function audit(): AuditRequest
    {
        $user = new User('ada@example.com', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $this->setId($user, 7);
        $audit = new AuditRequest('AUD-1', $user, AuditType::SEO, 'https://example.test', 'Optimiser la conversion');
        $this->setId($audit, 17);
        $audit->setStatus(AuditRequest::STATUS_REVIEW);

        $item = (new AuditChecklistItem('Performance', 'lcp', 'Chargement principal', 1))
            ->setIsCompliant(false)
            ->setComment('À corriger');
        $this->setId($item, 201);
        $audit->addItem($item);

        return $audit;
    }

    private function otherAudit(): AuditRequest
    {
        $user = new User('grace@example.com', 'Grace', 'Hopper', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $this->setId($user, 8);
        $audit = new AuditRequest('AUD-2', $user, AuditType::PERFORMANCE, 'https://other.test', null);
        $this->setId($audit, 18);

        return $audit;
    }

    private function setId(object $entity, int $id): void
    {
        $reflection = new \ReflectionObject($entity);
        $reflection->getProperty('id')->setValue($entity, $id);
    }
}
