<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Training\Controller;

use App\Module\Training\Domain\Entity\TrainingEnrollment;
use App\Module\Training\UI\Controller\Admin\UpdateTrainingEnrollmentStatusController;
use App\Module\Training\UI\Controller\Client\CreateTrainingEnrollmentController;
use App\Module\Training\UI\Controller\PublicApi\ListTrainingsController;
use App\Tests\Unit\Module\Training\TrainingIntegrationTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class TrainingPublicAndClientControllersIntegrationTest extends TrainingIntegrationTestCase
{
    public function testTrainingListStatusAndEnrollmentControllers(): void
    {
        $em = $this->entityManager();
        [$training, $session, $user] = $this->persistTrainingGraph($em);
        $formatter = $this->formatter($em);

        $list = new ListTrainingsController($this->trainingRepository($em), $formatter);
        $listPayload = json_decode((string) $list(Request::create('/?category=web&page=1&perPage=5'))->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('SEO', $listPayload['data']['items'][0]['title']);

        $enrollment = new TrainingEnrollment($session, $user, 0);
        $em->persist($enrollment);
        $em->flush();
        $status = new UpdateTrainingEnrollmentStatusController($this->enrollmentRepository($em), $formatter, $this->writer($em));
        self::assertSame(Response::HTTP_NOT_FOUND, $status(999, Request::create('/', 'PATCH', [], [], [], [], '{"status":"paid"}'))->getStatusCode());
        self::assertSame(Response::HTTP_BAD_REQUEST, $status((int) $enrollment->getId(), Request::create('/', 'PATCH', [], [], [], [], '{"status":"bogus"}'))->getStatusCode());
        self::assertSame(Response::HTTP_OK, $status((int) $enrollment->getId(), Request::create('/', 'PATCH', [], [], [], [], '{"status":"paid"}'))->getStatusCode());
        self::assertNotNull($enrollment->getPaidAt());

        $checkoutUser = $this->user('checkout@example.com');
        $em->persist($checkoutUser);
        $em->flush();
        $checkout = $this->checkoutService($em);
        $controller = new CreateTrainingEnrollmentController($checkout, $formatter);
        $controller->setContainer($this->controllerContainer($checkoutUser));
        $request = Request::create('/', 'POST', [], [], [], [], json_encode([
            'sessionId' => $session->getId(),
            'startsAt' => self::ENROLLMENT_START,
        ], JSON_THROW_ON_ERROR));
        self::assertSame(Response::HTTP_CREATED, $controller($request)->getStatusCode());
        self::assertSame(Response::HTTP_OK, $controller($request)->getStatusCode());

        $errorEm = $this->entityManager();
        [, , $errorUser] = $this->persistTrainingGraph($errorEm);
        $errorController = new CreateTrainingEnrollmentController($this->checkoutService($errorEm), $this->formatter($errorEm));
        $errorController->setContainer($this->controllerContainer($errorUser));
        self::assertSame(Response::HTTP_BAD_REQUEST, $controller(Request::create('/', 'POST', [], [], [], [], json_encode([
            'sessionId' => $session->getId(),
            'startsAt' => '',
        ], JSON_THROW_ON_ERROR)))->getStatusCode());
        self::assertSame(Response::HTTP_BAD_REQUEST, $controller(Request::create('/', 'POST', [], [], [], [], '{'))->getStatusCode());
        self::assertSame(Response::HTTP_NOT_FOUND, $errorController(Request::create('/', 'POST', [], [], [], [], '{"sessionId":0,"startsAt":"2026-08-12T09:00:00+00:00"}'))->getStatusCode());

        $anonymousController = new CreateTrainingEnrollmentController($this->checkoutService($this->entityManager()), $formatter);
        $anonymousController->setContainer($this->controllerContainer(null));
        self::assertSame(Response::HTTP_BAD_REQUEST, $anonymousController(Request::create('/', 'POST', [], [], [], [], '{"sessionId":1,"startsAt":"2026-08-12T09:00:00+00:00"}'))->getStatusCode());
    }
}
