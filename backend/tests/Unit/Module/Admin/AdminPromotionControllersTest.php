<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Admin;

use App\Module\Admin\UI\Promotion\Controller\CreatePromotionController;
use App\Module\Admin\UI\Promotion\Controller\DeletePromotionController;
use App\Module\Admin\UI\Promotion\Controller\GetPromotionController;
use App\Module\Admin\UI\Promotion\Controller\ListPromotionAudiencesController;
use App\Module\Admin\UI\Promotion\Controller\ListPromotionsController;
use App\Module\Admin\UI\Promotion\Controller\UpdatePromotionController;
use App\Module\Promotion\Application\Calculator\CartSubtotalCalculator;
use App\Module\Promotion\Application\Calculator\PromotionDiscountCalculator;
use App\Module\Promotion\Application\Calculator\PromotionEngine;
use App\Module\Promotion\Application\Handler\CreatePromotionHandler;
use App\Module\Promotion\Application\Handler\DeletePromotionHandler;
use App\Module\Promotion\Application\Handler\UpdatePromotionHandler;
use App\Module\Promotion\Application\Policy\PromotionEligibilityPolicy;
use App\Module\Promotion\Application\Provider\PromotionAudienceProvider;
use App\Module\Promotion\Application\Projection\PromotionFormatter;
use App\Module\Promotion\Application\Writer\PromotionDataApplier;
use App\Module\Promotion\Infrastructure\Repository\PromotionRepository;
use App\Shared\Infrastructure\Doctrine\DoctrineUnitOfWork;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class AdminPromotionControllersTest extends AdminModuleIntegrationTestCase
{
    public function testAdminPromotionControllers(): void
    {
        $em = $this->entityManager();
        $persistence = new DoctrineUnitOfWork($em);
        $repository = new PromotionRepository($this->registry($em));
        $validator = $this->validator(2);
        $formatter = new PromotionFormatter();

        $applier = new PromotionDataApplier();
        $create = new CreatePromotionController(new CreatePromotionHandler($persistence, $applier), $validator, $formatter);
        self::assertSame(Response::HTTP_BAD_REQUEST, $create(Request::create('/', 'POST', server: [], content: '{bad'))->getStatusCode());
        $created = $create($this->jsonRequest($this->promotionPayload()));
        self::assertSame(Response::HTTP_CREATED, $created->getStatusCode());
        $promotionId = (int) $this->payload($created)['data']['promotion']['id'];

        self::assertSame(Response::HTTP_OK, (new ListPromotionAudiencesController(new PromotionEngine(
            $repository,
            new PromotionFormatter(),
            new PromotionAudienceProvider(),
            new CartSubtotalCalculator(),
            new PromotionDiscountCalculator(),
            new PromotionEligibilityPolicy(),
            new MockClock('2026-08-11T10:00:00+00:00'),
        )))()->getStatusCode());
        self::assertSame(Response::HTTP_OK, (new ListPromotionsController($repository, $formatter))(Request::create('/?page=1&perPage=5'))->getStatusCode());
        self::assertSame(Response::HTTP_NOT_FOUND, (new GetPromotionController($repository, $formatter))(999)->getStatusCode());
        self::assertSame(Response::HTTP_OK, (new GetPromotionController($repository, $formatter))($promotionId)->getStatusCode());

        $update = new UpdatePromotionController($repository, new UpdatePromotionHandler($persistence, $applier), $validator, $formatter);
        self::assertSame(Response::HTTP_NOT_FOUND, $update(999, $this->jsonRequest($this->promotionPayload(), 'PUT'))->getStatusCode());
        self::assertSame(Response::HTTP_BAD_REQUEST, $update($promotionId, Request::create('/', 'PUT', server: [], content: '{bad'))->getStatusCode());
        self::assertSame(Response::HTTP_OK, $update($promotionId, $this->jsonRequest($this->promotionPayload(['name' => 'Updated']), 'PUT'))->getStatusCode());

        $delete = new DeletePromotionController($repository, new DeletePromotionHandler($persistence));
        self::assertSame(Response::HTTP_NOT_FOUND, $delete(999)->getStatusCode());
        self::assertSame(Response::HTTP_OK, $delete($promotionId)->getStatusCode());
    }
}
