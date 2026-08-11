<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Quote\Controller;

use App\Module\Order\Application\Projection\OrderFormatter;
use App\Module\Order\Application\Projection\OrderItemFormatter;
use App\Module\Order\Application\Projection\OrderStatusLabelFormatter;
use App\Module\Order\Domain\Workflow\OrderStatusWorkflow;
use App\Module\Quote\Application\Calculator\QuoteCalculator;
use App\Module\Quote\Application\Projection\QuoteFormatter;
use App\Module\Quote\Domain\Entity\ServiceOffering;
use App\Module\Quote\Infrastructure\Repository\ServiceOfferingRepository;
use App\Module\Quote\UI\Controller\PublicApi\GetServiceController;
use App\Module\Rating\Application\Projection\ProductReviewFormatter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

final class GetServiceControllerTest extends TestCase
{
    public function testHandlesFoundAndNotFound(): void
    {
        $repository = $this->createMock(ServiceOfferingRepository::class);
        $repository->expects(self::exactly(2))
            ->method('find')
            ->willReturnCallback(function (int $id): ?ServiceOffering {
                return match ($id) {
                    99 => null,
                    7 => $this->serviceEntity(),
                    default => throw new \LogicException('Unexpected service id.'),
                };
            });

        $controller = new GetServiceController(
            $repository,
            new QuoteFormatter(
                new QuoteCalculator(),
                new OrderFormatter(
                    new OrderStatusLabelFormatter(),
                    new OrderItemFormatter(new ProductReviewFormatter()),
                    new OrderStatusWorkflow(),
                ),
            ),
        );

        $missing = $controller(99);
        $missingPayload = json_decode((string) $missing->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(Response::HTTP_NOT_FOUND, $missing->getStatusCode());
        self::assertSame('error', $missingPayload['status']);

        $found = $controller(7);
        $foundPayload = json_decode((string) $found->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(Response::HTTP_OK, $found->getStatusCode());
        self::assertSame('Audit SEO', $foundPayload['data']['title']);
        self::assertSame('2 heures', $foundPayload['data']['durationLabel']);
        self::assertSame(20, $foundPayload['data']['vatRate']);
    }

    private function serviceEntity(): ServiceOffering
    {
        $service = new ServiceOffering('Audit SEO', 15000, 2000);
        $service
            ->setDescription('Desc')
            ->setUnit('heure')
            ->setDurationValue(2)
            ->setDurationUnit('hour');
        $this->setId($service, 7);

        return $service;
    }

    private function setId(object $entity, int $id): void
    {
        $reflection = new \ReflectionObject($entity);
        $reflection->getProperty('id')->setValue($entity, $id);
    }
}
