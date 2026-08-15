<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Misc;

use App\Module\Order\Application\Projection\OrderFormatter;
use App\Module\Order\Application\Projection\OrderItemFormatter;
use App\Module\Order\Application\Projection\OrderStatusLabelFormatter;
use App\Module\Order\Domain\Workflow\OrderStatusWorkflow;
use App\Module\Quote\Application\Calculator\QuoteCalculator;
use App\Module\Quote\Application\Projection\QuoteFormatter;
use App\Module\Rating\Application\Projection\ProductReviewFormatter;
use App\Module\Service\Application\Projection\ServiceFormatter;
use App\Module\User\Domain\Entity\User;
use PHPUnit\Framework\TestCase;

abstract class MiscSupportTestCase extends TestCase
{
    protected function user(): User
    {
        $user = new User('ada@example.com', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $user->setPassword('hashed');

        return $user;
    }

    protected function setId(object $entity, int $id): void
    {
        $reflection = new \ReflectionObject($entity);
        $reflection->getProperty('id')->setValue($entity, $id);
    }

    protected function coverPrivateConstructor(string $className): void
    {
        $reflection = new \ReflectionClass($className);
        $constructor = $reflection->getConstructor();
        self::assertNotNull($constructor);
        $constructor->setAccessible(true);
        $constructor->invoke($reflection->newInstanceWithoutConstructor());
    }

    protected function quoteFormatter(): QuoteFormatter
    {
        return new QuoteFormatter(
            new QuoteCalculator(),
            new OrderFormatter(
                new OrderStatusLabelFormatter(),
                new OrderItemFormatter(new ProductReviewFormatter()),
                new OrderStatusWorkflow(),
            ),
        );
    }

    protected function serviceFormatter(): ServiceFormatter
    {
        return new ServiceFormatter();
    }
}
