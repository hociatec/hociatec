<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Appointment\Controller;

use App\Module\Admin\UI\Appointment\Controller\CreatePrestationController as AdminCreatePrestationController;
use App\Module\Admin\UI\Appointment\Controller\DeletePrestationController as AdminDeletePrestationController;
use App\Module\Admin\UI\Appointment\Controller\GetConfigurationController as AdminGetConfigurationController;
use App\Module\Admin\UI\Appointment\Controller\ListAppointmentsController as AdminListAppointmentsController;
use App\Module\Admin\UI\Appointment\Controller\ListPrestationController as AdminListPrestationController;
use App\Module\Admin\UI\Appointment\Controller\ShowPrestationController as AdminShowPrestationController;
use App\Module\Admin\UI\Appointment\Controller\UpdateConfigurationController as AdminUpdateConfigurationController;
use App\Module\Admin\UI\Appointment\Controller\UpdatePrestationController as AdminUpdatePrestationController;
use App\Module\Appointment\Application\Mapper\WorkingDayPayloadMapper;
use App\Module\Appointment\Application\Workflow\PrestationService;
use App\Module\Appointment\Application\Workflow\WorkingDayConfigurationService;
use App\Module\Appointment\Domain\Entity\Appointment;
use App\Module\Appointment\Infrastructure\Persistence\PrestationPersistence;
use App\Module\Appointment\Infrastructure\Persistence\WorkingDayConfigurationPersistence;
use App\Tests\Unit\Module\Appointment\AppointmentIntegrationTestCase;
use Symfony\Component\HttpFoundation\Request;

final class AdminAppointmentControllersIntegrationTest extends AppointmentIntegrationTestCase
{
    public function testAdminAppointmentControllersCoverPrestationsConfigurationAndAppointments(): void
    {
        [$user, $prestation] = $this->seedSchedule();
        $appointment = new Appointment($user, $prestation, new \DateTimeImmutable('2026-08-17T09:00:00+00:00'));
        $this->entityManager()->persist($appointment);
        $this->entityManager()->flush();

        $prestationService = $this->prestationService();
        $create = new AdminCreatePrestationController($prestationService, $this->validator());
        self::assertSame(400, $create(Request::create('/', 'POST', server: [], content: '{bad'))->getStatusCode());
        self::assertSame(422, $create($this->jsonRequest(['name' => 'Broken', 'durationMinutes' => 30, 'price' => 'bad']))->getStatusCode());
        self::assertSame(422, $create($this->jsonRequest(['name' => 'Too expensive', 'durationMinutes' => 30, 'price' => 1000001]))->getStatusCode());
        self::assertSame(201, $create($this->jsonRequest(['name' => 'Audit express', 'durationMinutes' => 30, 'price' => '49,90']))->getStatusCode());
        self::assertSame(201, $create($this->jsonRequest(['name' => 'Audit int', 'durationMinutes' => 45, 'price' => 50]))->getStatusCode());
        self::assertSame(201, $create($this->jsonRequest(['name' => 'Audit float', 'durationMinutes' => 45, 'price' => 50.5]))->getStatusCode());
        self::assertSame(500, (new AdminCreatePrestationController($this->failingPrestationService(), $this->validator()))($this->jsonRequest(['name' => 'Failure', 'durationMinutes' => 30, 'price' => 10]))->getStatusCode());

        $list = new AdminListPrestationController($prestationService);
        self::assertSame(200, $list()->getStatusCode());

        $show = new AdminShowPrestationController($this->prestations());
        self::assertSame(404, $show(999)->getStatusCode());
        self::assertSame(200, $show((int) $prestation->getId())->getStatusCode());

        $update = new AdminUpdatePrestationController($this->prestations(), $prestationService, $this->validator());
        self::assertSame(404, $update(999, $this->jsonRequest(['name' => 'Nope', 'durationMinutes' => 30, 'price' => 10], 'PUT'))->getStatusCode());
        self::assertSame(400, $update((int) $prestation->getId(), Request::create('/', 'PUT', server: [], content: '{bad'))->getStatusCode());
        self::assertSame(422, $update((int) $prestation->getId(), $this->jsonRequest(['name' => 'Broken', 'durationMinutes' => 30, 'price' => 'bad'], 'PUT'))->getStatusCode());
        self::assertSame(422, $update((int) $prestation->getId(), $this->jsonRequest(['name' => 'Too expensive', 'durationMinutes' => 30, 'price' => 1000001], 'PUT'))->getStatusCode());
        self::assertSame(200, $update((int) $prestation->getId(), $this->jsonRequest(['name' => 'Diagnostic updated', 'durationMinutes' => 75, 'price' => '120.20'], 'PUT'))->getStatusCode());
        self::assertSame(200, $update((int) $prestation->getId(), $this->jsonRequest(['name' => 'Diagnostic float', 'durationMinutes' => 75, 'price' => 120.2], 'PUT'))->getStatusCode());
        self::assertSame(500, (new AdminUpdatePrestationController($this->prestations(), $this->failingPrestationService(), $this->validator()))((int) $prestation->getId(), $this->jsonRequest(['name' => 'Failure', 'durationMinutes' => 30, 'price' => 10], 'PUT'))->getStatusCode());

        $adminAppointments = new AdminListAppointmentsController($this->appointments());
        self::assertSame(200, $adminAppointments(Request::create('/?page=1&perPage=5'))->getStatusCode());

        $configurationService = new WorkingDayConfigurationService($this->workingDays(), new WorkingDayConfigurationPersistence($this->entityManager()));
        $getConfiguration = new AdminGetConfigurationController($configurationService);
        self::assertSame(200, $getConfiguration()->getStatusCode());

        $updateConfiguration = new AdminUpdateConfigurationController($configurationService, new WorkingDayPayloadMapper(), $this->validator());
        self::assertSame(400, $updateConfiguration(Request::create('/', 'PUT', server: [], content: '{bad'))->getStatusCode());
        self::assertSame(400, $updateConfiguration($this->jsonRequest(['days' => [['dayOfWeek' => 0, 'isWorkingDay' => true, 'startTime' => '14:00', 'endTime' => '13:00']]], 'PUT'))->getStatusCode());
        self::assertSame(200, $updateConfiguration($this->jsonRequest(['days' => [
            ['dayOfWeek' => 0, 'isWorkingDay' => true, 'startTime' => '08:30', 'endTime' => '17:30', 'breaks' => [['start' => '12:00', 'end' => '12:30']]],
            ['dayOfWeek' => 6, 'isWorkingDay' => false],
        ]], 'PUT'))->getStatusCode());

        $delete = new AdminDeletePrestationController($this->prestations(), new PrestationService($this->prestations(), new PrestationPersistence($this->entityManager())));
        self::assertSame(404, $delete(999)->getStatusCode());
        $createdId = (int) $this->payload($create($this->jsonRequest(['name' => 'Temporary', 'durationMinutes' => 15, 'price' => 5])))['data']['id'];
        self::assertSame(200, $delete($createdId)->getStatusCode());
    }
}
