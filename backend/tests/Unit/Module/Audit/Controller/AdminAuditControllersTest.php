<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Audit\Controller;

use App\Module\Admin\UI\Audit\Controller\ListAuditsController;
use App\Module\Admin\UI\Audit\Controller\ShowAuditController;
use App\Module\Audit\Domain\Entity\AuditChecklistItem;
use App\Module\Audit\Domain\Entity\AuditEvent;
use App\Module\Audit\Domain\Entity\AuditRequest;
use App\Module\Audit\Domain\Entity\AuditType;
use App\Module\Audit\Infrastructure\Repository\AuditEventRepository;
use App\Module\Audit\Infrastructure\Repository\AuditRequestRepository;
use App\Module\Audit\Application\Projection\AuditMetadataFormatter;
use App\Module\User\Domain\Entity\User;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class AdminAuditControllersTest extends TestCase
{
    public function testListAuditsControllerBuildsPaginatedPayload(): void
    {
        $repository = $this->getMockBuilder(AuditRequestRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['findBy', 'count'])
            ->getMock();

        $audit = $this->audit();
        $repository->expects(self::once())
            ->method('findBy')
            ->with([], ['createdAt' => 'DESC'], 10, 10)
            ->willReturn([$audit]);
        $repository->expects(self::once())
            ->method('count')
            ->with([])
            ->willReturn(21);

        $response = (new ListAuditsController($repository, new AuditMetadataFormatter()))(
            Request::create('/?page=2&perPage=10', 'GET')
        );
        $payload = $this->payload($response);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(21, $payload['data']['meta']['total']);
        self::assertSame(3, $payload['data']['meta']['totalPages']);
        self::assertSame('AUD-1', $payload['data']['items'][0]['number']);
        self::assertSame('seo', $payload['data']['items'][0]['type']);
        self::assertSame('SEO', $payload['data']['items'][0]['typeLabel']);
        self::assertSame('En revue', $payload['data']['items'][0]['statusLabel']);
        self::assertSame('Ada Lovelace', $payload['data']['items'][0]['client']['name']);
    }

    public function testShowAuditControllerHandlesNotFoundAndFormatsDetails(): void
    {
        $audit = $this->audit();
        $event = new AuditEvent($audit, 'status_changed', 'Statut modifié', 7, 'Admin');
        $this->setId($event, 301);

        $repository = $this->getMockBuilder(AuditRequestRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['find'])
            ->getMock();
        $repository->expects(self::exactly(2))
            ->method('find')
            ->with(17)
            ->willReturnOnConsecutiveCalls(null, $audit);

        $events = $this->getMockBuilder(AuditEventRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['findByAudit'])
            ->getMock();
        $events->expects(self::once())
            ->method('findByAudit')
            ->with($audit, 'DESC')
            ->willReturn([$event]);

        $controller = new ShowAuditController($repository, $events, new AuditMetadataFormatter());

        $notFound = $controller->__invoke(17);
        self::assertSame(404, $notFound->getStatusCode());
        self::assertSame('Audit introuvable.', $this->payload($notFound)['message']);

        $response = $controller->__invoke(17);
        $payload = $this->payload($response);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('AUD-1', $payload['data']['number']);
        self::assertSame('review', $payload['data']['status']);
        self::assertSame('SEO', $payload['data']['typeLabel']);
        self::assertSame('Optimiser la conversion', $payload['data']['objectives']);
        self::assertSame('Performance', $payload['data']['items'][0]['category']);
        self::assertSame('Chargement principal', $payload['data']['items'][0]['label']);
        self::assertFalse($payload['data']['items'][0]['isCompliant']);
        self::assertSame('Statut modifié', $payload['data']['events'][0]['message']);
        self::assertSame('Admin', $payload['data']['events'][0]['actor']['name']);
    }

    private function audit(): AuditRequest
    {
        $user = new User('ada@example.com', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $this->setId($user, 7);

        $audit = new AuditRequest('AUD-1', $user, AuditType::SEO, 'https://example.test', 'Optimiser la conversion');
        $this->setId($audit, 17);
        $audit->setStatus(AuditRequest::STATUS_REVIEW);

        $item = (new AuditChecklistItem('Performance', 'lcp', 'Chargement principal', 1))
            ->setLevel('A')
            ->setIsCompliant(false)
            ->setComment('À corriger');
        $this->setId($item, 201);
        $audit->addItem($item);

        return $audit;
    }

    private function payload(object $response): array
    {
        return json_decode($response->getContent() ?: '{}', true, 512, JSON_THROW_ON_ERROR);
    }

    private function setId(object $entity, int $id): void
    {
        $reflection = new \ReflectionObject($entity);
        $reflection->getProperty('id')->setValue($entity, $id);
    }
}
