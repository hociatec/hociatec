<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Misc;

use App\Module\Appointment\Domain\Entity\Prestation;
use App\Module\Appointment\Domain\Entity\WorkingDayConfiguration;
use App\Module\Appointment\Infrastructure\Repository\PrestationRepository;
use App\Module\Appointment\Infrastructure\Repository\WorkingDayConfigurationRepository;
use App\Module\Audit\Domain\Entity\AuditRequest;
use App\Module\Audit\Domain\Entity\AuditType;
use App\Module\Audit\Infrastructure\Repository\AuditEventRepository;
use App\Module\Audit\Infrastructure\Repository\AuditRequestRepository;
use App\Module\Marketing\Domain\Entity\EmailTemplate;
use App\Module\Marketing\Infrastructure\Repository\EmailTemplateRepository;

final class RepositoryCustomMethodsTest extends RepositoryTestCase
{
    public function testRepositoriesWithSmallCustomMethodsBehaveAgainstBaseApis(): void
    {
        $workingDayRepository = $this->getMockBuilder(WorkingDayConfigurationRepository::class)
            ->setConstructorArgs([$this->registry(WorkingDayConfiguration::class)])
            ->onlyMethods(['findOneBy', 'createQueryBuilder'])
            ->getMock();
        $configuration = new WorkingDayConfiguration(1, true);
        $workingDayRepository->expects(self::once())->method('findOneBy')->with(['dayOfWeek' => 1])->willReturn($configuration);
        $workingDayRepository->expects(self::once())->method('createQueryBuilder')->with('w')->willReturn($this->queryBuilderReturning([$configuration]));
        self::assertSame($configuration, $workingDayRepository->findOneByDay(1));
        self::assertSame([$configuration], $workingDayRepository->findAllOrdered());

        $auditRequestRepository = $this->getMockBuilder(AuditRequestRepository::class)
            ->setConstructorArgs([$this->registry(AuditRequest::class)])
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();
        $auditRequestRepository->expects(self::once())->method('createQueryBuilder')->with('a')->willReturn($this->queryBuilderReturning(['items']));
        self::assertSame(['items'], $auditRequestRepository->findByUser($this->user()));

        $auditEventRepository = $this->getMockBuilder(AuditEventRepository::class)
            ->setConstructorArgs([$this->registry(\App\Module\Audit\Domain\Entity\AuditEvent::class)])
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();
        $audit = new AuditRequest('AUD-1', $this->user(), AuditType::SEO, 'https://example.com', null);
        $auditEventRepository->expects(self::once())->method('createQueryBuilder')->with('e')->willReturn($this->queryBuilderReturning(['events']));
        self::assertSame(['events'], $auditEventRepository->findByAudit($audit, 'ASC'));

        $prestationRepository = $this->getMockBuilder(PrestationRepository::class)
            ->setConstructorArgs([$this->registry(Prestation::class)])
            ->onlyMethods(['createQueryBuilder', 'getEntityManager'])
            ->getMock();
        $prestation = new Prestation('Diag', 30, 1000);
        $prestationRepository->expects(self::once())->method('createQueryBuilder')->with('p')->willReturn($this->queryBuilderReturning([$prestation]));
        $prestationRepository->expects(self::once())->method('getEntityManager')->willReturn($this->entityManagerForRemoval($prestation));
        self::assertSame([$prestation], $prestationRepository->findAllOrderedByName());
        $prestationRepository->remove($prestation);

        $emailTemplateRepository = $this->getMockBuilder(EmailTemplateRepository::class)
            ->setConstructorArgs([$this->registry(EmailTemplate::class)])
            ->onlyMethods(['findOneBy'])
            ->getMock();
        $template = new EmailTemplate('Welcome', 'welcome', 'account_created', 'Sujet', '<p>Hi</p>');
        $emailTemplateRepository->expects(self::exactly(2))
            ->method('findOneBy')
            ->willReturnOnConsecutiveCalls($template, $template);
        self::assertSame($template, $emailTemplateRepository->findOneBySlug('welcome'));
        self::assertSame($template, $emailTemplateRepository->findActiveOneByScenarioKey('account_created'));
    }
}
