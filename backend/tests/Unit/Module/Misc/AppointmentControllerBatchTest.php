<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Misc;

use App\Module\Admin\UI\Appointment\Controller\DeletePrestationController;
use App\Module\Admin\UI\Appointment\Controller\GetConfigurationController;
use App\Module\Admin\UI\Appointment\Controller\ListPrestationController;
use App\Module\Admin\UI\Appointment\Controller\ShowPrestationController;
use App\Module\Appointment\UI\Controller\PublicApi\PublicPrestationController;
use App\Module\Appointment\UI\Controller\PublicApi\PublicWorkingDayController;
use App\Module\Appointment\Domain\Entity\Prestation;
use App\Module\Appointment\Domain\Entity\WorkingDayConfiguration;
use App\Module\Appointment\Infrastructure\Repository\PrestationRepository;
use App\Module\Appointment\Infrastructure\Repository\WorkingDayConfigurationRepository;
use App\Module\Appointment\Infrastructure\Persistence\PrestationPersistence;
use App\Module\Appointment\Application\Workflow\PrestationService;
use App\Module\Appointment\Infrastructure\Persistence\WorkingDayConfigurationPersistence;
use App\Module\Appointment\Application\Workflow\WorkingDayConfigurationService;
use App\Module\Appointment\UI\Response\PublicAppointmentResponseMapper;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validation;

final class AppointmentControllerBatchTest extends TestCase
{
    public function testPrestationControllers(): void
    {
        $prestation = new Prestation('Diagnostic', 45, 1500);
        $this->setId($prestation, 4);

        $showDeleteRepository = $this->getMockBuilder(PrestationRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['find', 'remove'])
            ->getMock();
        $showDeleteRepository->expects(self::exactly(4))
            ->method('find')
            ->willReturnOnConsecutiveCalls(null, $prestation, null, $prestation);
        $deleteEntityManager = $this->createMock(EntityManagerInterface::class);
        $deleteEntityManager->expects(self::once())->method('remove')->with($prestation);
        $deleteEntityManager->expects(self::once())->method('flush');

        $show = new ShowPrestationController($showDeleteRepository);
        self::assertSame(Response::HTTP_NOT_FOUND, $show(404)->getStatusCode());
        $showPayload = json_decode((string) $show(4)->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('Diagnostic', $showPayload['data']['name']);

        $delete = new DeletePrestationController($showDeleteRepository, new PrestationService($showDeleteRepository, new PrestationPersistence($deleteEntityManager), Validation::createValidator()));
        self::assertSame(Response::HTTP_NOT_FOUND, $delete(404)->getStatusCode());
        self::assertSame(Response::HTTP_OK, $delete(4)->getStatusCode());

        $listRepository = $this->getMockBuilder(PrestationRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['findAllOrderedByName', 'countAll'])
            ->getMock();
        $listRepository->expects(self::exactly(2))->method('findAllOrderedByName')->willReturn([$prestation]);
        $listRepository->expects(self::once())->method('countAll')->willReturn(1);
        $persistence = new PrestationPersistence($this->createMock(EntityManagerInterface::class));
        $service = new PrestationService($listRepository, $persistence, Validation::createValidator());

        $listPayload = json_decode((string) (new ListPrestationController($service))()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(1500, $listPayload['data']['items'][0]['priceCents']);

        $publicPayload = json_decode((string) (new PublicPrestationController($service, new PublicAppointmentResponseMapper()))()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(45, $publicPayload['data']['items'][0]['durationMinutes']);
    }

    public function testConfigurationControllersAndService(): void
    {
        $monday = new WorkingDayConfiguration(
            0,
            true,
            new \DateTimeImmutable('09:00'),
            new \DateTimeImmutable('18:00'),
            [['start' => '12:00', 'end' => '13:00']],
        );

        $listRepository = $this->getMockBuilder(WorkingDayConfigurationRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['findAllOrdered'])
            ->getMock();
        $listRepository->expects(self::exactly(3))->method('findAllOrdered')->willReturn([$monday]);

        $service = new WorkingDayConfigurationService(
            $listRepository,
            new WorkingDayConfigurationPersistence($this->createMock(EntityManagerInterface::class)),
        );

        $adminPayload = json_decode((string) (new GetConfigurationController($service))()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('Lundi', $adminPayload['data']['days'][0]['dayLabel']);

        $publicPayload = json_decode((string) (new PublicWorkingDayController($service, new PublicAppointmentResponseMapper()))()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('09:00', $publicPayload['data']['days'][0]['startTime']);

        self::assertSame([$monday], $service->list());
    }

    private function setId(object $entity, int $id): void
    {
        $reflection = new \ReflectionObject($entity);
        $reflection->getProperty('id')->setValue($entity, $id);
    }
}
