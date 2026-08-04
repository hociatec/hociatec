<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Misc;

use App\Module\Admin\UI\Promotion\Controller\CreatePromotionController;
use App\Module\Admin\UI\Promotion\Controller\GetPromotionController;
use App\Module\Admin\UI\Promotion\Controller\ListPromotionsController;
use App\Module\Admin\UI\Quote\Controller\DeleteServiceController;
use App\Module\Promotion\Application\Handler\CreatePromotionHandler;
use App\Module\Promotion\Application\Writer\PromotionDataApplier;
use App\Module\Promotion\Domain\Entity\Promotion;
use App\Module\Promotion\Infrastructure\Repository\PromotionRepository;
use App\Module\Quote\Domain\Entity\Service;
use App\Module\Quote\Infrastructure\Repository\ServiceRepository;
use App\Shared\Infrastructure\Doctrine\DoctrineUnitOfWork;
use App\Shared\Infrastructure\Validation\ConstraintViolationFormatter;
use App\Shared\Infrastructure\Validation\DtoValidator;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validation;

final class PromotionAndQuoteTailControllerTest extends TestCase
{
    public function testPromotionReadControllersAndCreateController(): void
    {
        $promotion = new Promotion('Summer', 'summer', 'percent', 10, 'all_users');
        $this->setId($promotion, 4);

        $promotions = $this->createMock(PromotionRepository::class);
        $promotions->expects(self::exactly(2))->method('find')->willReturnOnConsecutiveCalls(null, $promotion);
        $promotions->expects(self::once())->method('findBy')->with([], ['updatedAt' => 'DESC'], 20, 20)->willReturn([$promotion]);
        $promotions->expects(self::once())->method('count')->with([])->willReturn(21);

        $get = new GetPromotionController($promotions);
        self::assertSame(Response::HTTP_NOT_FOUND, $get(404)->getStatusCode());
        $getPayload = json_decode((string) $get(4)->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('summer', $getPayload['data']['promotion']['slug']);

        $list = new ListPromotionsController($promotions);
        $listPayload = json_decode((string) $list(new Request(['page' => '2']))->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(2, $listPayload['data']['meta']['page']);
        self::assertSame('Summer', $listPayload['data']['items'][0]['name']);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('persist')->with(self::isInstanceOf(Promotion::class));
        $entityManager->expects(self::once())->method('flush');

        $controller = new CreatePromotionController(
            new CreatePromotionHandler(new DoctrineUnitOfWork($entityManager), new PromotionDataApplier()),
            new DtoValidator(
                Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator(),
                new ConstraintViolationFormatter(),
            ),
        );

        self::assertSame(Response::HTTP_BAD_REQUEST, $controller(new Request(content: '{"name":'))->getStatusCode());

        $createdPayload = json_decode((string) $controller(new Request(content: json_encode([
            'name' => 'VIP',
            'slug' => 'vip',
            'description' => 'Promo',
            'discountType' => 'fixed_cents',
            'discountValue' => 1500,
            'audienceKey' => 'all_users',
            'criteria' => ['minimumCartTotalCents' => 0],
            'isActive' => true,
        ], JSON_THROW_ON_ERROR)))->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('VIP', $createdPayload['data']['promotion']['name']);
    }

    public function testDeleteServiceController(): void
    {
        $serviceEntity = new Service('Audit', 10000, 2000);
        $this->setId($serviceEntity, 7);

        $services = $this->createMock(ServiceRepository::class);
        $services->expects(self::exactly(2))->method('find')->willReturnOnConsecutiveCalls(null, $serviceEntity);
        $services->expects(self::once())->method('delete')->with($serviceEntity);

        $controller = new DeleteServiceController($services, new \App\Module\Admin\Application\Quote\Service\DeleteQuoteServiceHandler(
            $services,
            new \App\Module\Quote\Application\Persistence\QuotePersistence($this->createMock(\Doctrine\ORM\EntityManagerInterface::class)),
        ));
        self::assertSame(Response::HTTP_NOT_FOUND, $controller(404)->getStatusCode());
        self::assertSame(Response::HTTP_OK, $controller(7)->getStatusCode());
    }

    private function setId(object $entity, int $id): void
    {
        $reflection = new \ReflectionObject($entity);
        $reflection->getProperty('id')->setValue($entity, $id);
    }
}
