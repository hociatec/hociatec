<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Appointment\Controller;

use App\Module\Appointment\Domain\Entity\Appointment;
use App\Module\Appointment\Domain\Security\AppointmentAccessPolicy;
use App\Module\Appointment\UI\Controller\Client\CreateAppointmentController;
use App\Module\Appointment\UI\Controller\Client\ListMyAppointmentsController;
use App\Module\Appointment\UI\Controller\Client\UpdateAppointmentStatusController;
use App\Module\Appointment\UI\Controller\PublicApi\PublicAvailabilityController;
use App\Tests\Unit\Module\Appointment\AppointmentIntegrationTestCase;
use Symfony\Component\HttpFoundation\Request;

final class PublicAndClientAppointmentControllersIntegrationTest extends AppointmentIntegrationTestCase
{
    public function testPublicAvailabilityControllerCoversValidationNotFoundAndSlots(): void
    {
        [, $prestation] = $this->seedSchedule();
        $controller = new PublicAvailabilityController($this->availability(), $this->prestations());

        self::assertSame(400, $controller(Request::create('/'))->getStatusCode());
        self::assertSame(400, $controller(Request::create('/?start=2026-08-17T11:00:00%2B00:00&end=2026-08-17T10:00:00%2B00:00&prestationId=1'))->getStatusCode());
        self::assertSame(404, $controller(Request::create('/?start=2026-08-17T08:00:00%2B00:00&end=2026-08-17T12:00:00%2B00:00&prestationId=999'))->getStatusCode());

        $response = $controller(Request::create(sprintf(
            '/?start=2026-08-17T08:00:00%%2B00:00&end=2026-08-17T12:00:00%%2B00:00&prestationId=%d',
            $prestation->getId(),
        )));
        $payload = $this->payload($response);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('2026-08-17T09:00:00+00:00', $payload['data']['slots'][0]['start']);
    }

    public function testClientControllersCreateListAndUpdateStatuses(): void
    {
        [$user, $prestation] = $this->seedSchedule();
        $service = $this->appointmentService();
        $formatter = $this->appointmentFormatter();

        $create = new CreateAppointmentController($service, $formatter, $this->prestations(), $this->validator());
        $create->setContainer($this->container($user));
        self::assertSame(400, $create(Request::create('/', 'POST', server: [], content: '{bad'))->getStatusCode());
        self::assertSame(404, $create($this->jsonRequest(['prestationId' => 999, 'startAt' => '2026-08-17T09:00:00+00:00']))->getStatusCode());
        self::assertSame(422, $create($this->jsonRequest(['prestationId' => $prestation->getId(), 'startAt' => 'not-a-date']))->getStatusCode());

        $created = $create($this->jsonRequest(['prestationId' => $prestation->getId(), 'startAt' => '2026-08-17T09:00:00+00:00']));
        self::assertSame(201, $created->getStatusCode());
        $appointmentId = (int) $this->payload($created)['data']['id'];

        $list = new ListMyAppointmentsController($service);
        $list->setContainer($this->container($user));
        $listPayload = $this->payload($list());
        self::assertSame($appointmentId, $listPayload['data']['upcoming'][0]['id']);

        $update = new UpdateAppointmentStatusController($this->appointments(), $service, $formatter, $this->validator(), new AppointmentAccessPolicy());
        $update->setContainer($this->container($user));
        self::assertSame(404, $update(999, $this->jsonRequest(['status' => Appointment::STATUS_CANCELLED], 'PATCH'))->getStatusCode());
        self::assertSame(400, $update($appointmentId, Request::create('/', 'PATCH', server: [], content: '{bad'))->getStatusCode());

        $cancelled = $update($appointmentId, $this->jsonRequest(['status' => Appointment::STATUS_CANCELLED], 'PATCH'));
        self::assertSame(200, $cancelled->getStatusCode());
        self::assertSame(Appointment::STATUS_CANCELLED, $this->appointments()->find($appointmentId)?->getStatus());

        $other = $this->persistUser('other-appointment@example.test');
        $update->setContainer($this->container($other, false));
        self::assertSame(403, $update($appointmentId, $this->jsonRequest(['status' => Appointment::STATUS_CONFIRMED], 'PATCH'))->getStatusCode());

        $update->setContainer($this->container($other, true));
        self::assertSame(200, $update($appointmentId, $this->jsonRequest(['status' => Appointment::STATUS_CONFIRMED], 'PATCH'))->getStatusCode());
    }
}
