<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Audit;

use App\Module\Audit\Application\Workflow\AuditEventLogger;
use App\Module\Audit\Application\Workflow\CustomerAuditPortalService;
use App\Module\Audit\Domain\Entity\AuditChecklistItem;
use App\Module\Audit\Domain\Entity\AuditEvent;
use App\Module\Audit\Domain\Entity\AuditRequest;
use App\Module\Audit\Domain\Security\AuditAccessPolicy;
use App\Module\Audit\Infrastructure\Repository\AuditChecklistItemRepository;
use App\Module\Audit\Infrastructure\Repository\AuditEventRepository;
use App\Module\Audit\Infrastructure\Repository\AuditRequestRepository;
use App\Module\User\Domain\Entity\User;
use App\Shared\Infrastructure\Doctrine\DoctrineUnitOfWork;
use App\Shared\Infrastructure\Validation\ConstraintViolationFormatter;
use App\Shared\Infrastructure\Validation\DtoValidator;
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

abstract class AuditIntegrationTestCase extends TestCase
{
    private ?EntityManager $entityManager = null;

    protected function tearDown(): void
    {
        $this->entityManager?->close();
        $this->entityManager = null;
    }

    protected function persistUser(string $email): User
    {
        $user = new User($email, 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $user->setPassword('hashed');
        $this->entityManager()->persist($user);
        $this->entityManager()->flush();

        return $user;
    }

    protected function entityManager(): EntityManager
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

    protected function registry(): ManagerRegistry
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($this->entityManager());

        return $registry;
    }

    protected function auditRequests(): AuditRequestRepository
    {
        return new AuditRequestRepository($this->registry());
    }

    protected function auditEvents(): AuditEventRepository
    {
        return new AuditEventRepository($this->registry());
    }

    protected function auditChecklistItems(): AuditChecklistItemRepository
    {
        return new AuditChecklistItemRepository($this->registry());
    }

    protected function eventLogger(): AuditEventLogger
    {
        return new AuditEventLogger(new DoctrineUnitOfWork($this->entityManager()));
    }

    protected function customerPortal(?\App\Module\Audit\Application\Port\AuditPdfRenderer $pdf = null): CustomerAuditPortalService
    {
        return new CustomerAuditPortalService(
            $this->auditRequests(),
            $this->auditEvents(),
            new \App\Module\Audit\Application\Projection\AuditMetadataFormatter(),
            new AuditAccessPolicy(),
            $this->eventLogger(),
            $pdf,
        );
    }

    protected function validator(): DtoValidator
    {
        return new DtoValidator(Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator(), new ConstraintViolationFormatter());
    }

    /** @param array<string,mixed> $payload */
    protected function jsonRequest(array $payload): Request
    {
        return Request::create('/', 'POST', server: ['CONTENT_TYPE' => 'application/json'], content: json_encode($payload, JSON_THROW_ON_ERROR));
    }

    /** @return array<string,mixed> */
    protected function payload(Response $response): array
    {
        return json_decode($response->getContent() ?: '{}', true, 512, JSON_THROW_ON_ERROR);
    }

    protected function container(User $user): Container
    {
        $tokenStorage = new TokenStorage();
        $tokenStorage->setToken(new \Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken(new \App\Module\Auth\Infrastructure\Security\SymfonySecurityUser($user), 'main', $user->getRoles()));

        $container = new Container();
        $container->set('security.token_storage', $tokenStorage);

        return $container;
    }
}
