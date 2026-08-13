<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Training\Controller;

use App\Module\Training\UI\Controller\Admin\SaveTrainingCategoryController;
use App\Module\Training\UI\Controller\Admin\SaveTrainingController;
use App\Module\Training\UI\Controller\Admin\SaveTrainingSessionController;
use App\Tests\Unit\Module\Training\TrainingIntegrationTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class TrainingAdminControllersIntegrationTest extends TrainingIntegrationTestCase
{
    public function testTrainingSaveControllersCoverCreateUpdateAndValidationBranches(): void
    {
        $em = $this->entityManager();
        [$training] = $this->persistTrainingGraph($em);
        $categories = $this->categoryRepository($em);
        $trainings = $this->trainingRepository($em);
        $sessions = $this->sessionRepository($em);
        $formatter = $this->formatter($em);
        $validator = $this->validator(8);

        $categoryController = new SaveTrainingCategoryController($categories, $this->writer($em), $this->categoryFormatter());
        self::assertSame(Response::HTTP_BAD_REQUEST, $categoryController(Request::create('/', 'POST', [], [], [], [], '{"name":""}'))->getStatusCode());
        self::assertSame(Response::HTTP_NOT_FOUND, $categoryController(Request::create('/', 'POST', [], [], [], [], '{"name":"Missing"}'), 999)->getStatusCode());
        self::assertSame(Response::HTTP_BAD_REQUEST, $categoryController(Request::create('/', 'POST', [], [], [], [], '{"name":"Duplicate","slug":"web"}'))->getStatusCode());
        self::assertSame(Response::HTTP_CREATED, $categoryController(Request::create('/', 'POST', [], [], [], [], '{"name":"Cloud","slug":"cloud","position":2,"isActive":false}'))->getStatusCode());

        $trainingController = new SaveTrainingController($trainings, $this->writer($em), $formatter, $validator);
        self::assertSame(Response::HTTP_NOT_FOUND, $trainingController(Request::create('/', 'POST', [], [], [], [], json_encode($this->trainingPayload(), JSON_THROW_ON_ERROR)), 999)->getStatusCode());
        self::assertSame(Response::HTTP_CREATED, $trainingController(Request::create('/', 'POST', [], [], [], [], json_encode($this->trainingPayload(['title' => 'Cloud', 'slug' => 'cloud-training']), JSON_THROW_ON_ERROR)))->getStatusCode());
        self::assertSame(Response::HTTP_OK, $trainingController(Request::create('/', 'POST', [], [], [], [], json_encode($this->trainingPayload(['title' => 'SEO Updated']), JSON_THROW_ON_ERROR)), $training->getId())->getStatusCode());

        $sessionController = new SaveTrainingSessionController($trainings, $sessions, $formatter, $this->writer($em), $validator);
        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $sessionController(Request::create('/', 'POST', [], [], [], [], json_encode($this->sessionPayload($training, [
            'startsAt' => self::ADMIN_SESSION_START,
            'endsAt' => self::ADMIN_SESSION_START,
        ]), JSON_THROW_ON_ERROR)))->getStatusCode());
        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $sessionController(Request::create('/', 'POST', [], [], [], [], json_encode($this->sessionPayload($training, [
            'dailyStartTime' => '18:00',
            'dailyEndTime' => '08:00',
        ]), JSON_THROW_ON_ERROR)))->getStatusCode());
        self::assertSame(Response::HTTP_NOT_FOUND, $sessionController(Request::create('/', 'POST', [], [], [], [], json_encode($this->sessionPayload($training, ['trainingId' => 999]), JSON_THROW_ON_ERROR)))->getStatusCode());
        self::assertSame(Response::HTTP_CREATED, $sessionController(Request::create('/', 'POST', [], [], [], [], json_encode($this->sessionPayload($training), JSON_THROW_ON_ERROR)))->getStatusCode());
        self::assertSame(Response::HTTP_NOT_FOUND, $sessionController(Request::create('/', 'POST', [], [], [], [], json_encode($this->sessionPayload($training), JSON_THROW_ON_ERROR)), 999)->getStatusCode());
    }
}
