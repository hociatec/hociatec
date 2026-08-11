<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\TradeIn\Controller;

use App\Module\Catalog\Infrastructure\Repository\ProductRepository;
use App\Module\Notification\Application\Workflow\CommunicationPreferences;
use App\Module\TradeIn\UI\Http\PublicTradeInRateLimiter;
use App\Module\TradeIn\UI\Controller\CreateMyTradeInController;
use App\Module\TradeIn\UI\Controller\CreatePublicTradeInController;
use App\Tests\Unit\Module\TradeIn\TradeInIntegrationTestCase;
use App\Shared\Infrastructure\Http\RateLimitKeyFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class TradeInCreateControllersTest extends TradeInIntegrationTestCase
{
    public function testCreateControllersCoverRibCatalogAndSuccessBranches(): void
    {
        $user = $this->user([CommunicationPreferences::EMAIL]);
        $this->setId($user, 42);
        $product = $this->product();
        $this->setId($product, 9);
        $products = $this->getMockBuilder(ProductRepository::class)->disableOriginalConstructor()->getMock();
        $products->method('find')->willReturnMap([[9, null], [10, $product]]);
        $service = $this->tradeInService($this->mockEntityManager(self::any()));

        $tradeInFormatter = new \App\Module\TradeIn\Application\Projection\TradeInFormatter();
        $my = new CreateMyTradeInController($service, $this->validator(2), $products, $tradeInFormatter);
        $my->setContainer($this->controllerContainer($user));
        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $my(Request::create('/', 'POST', $this->payload()))->getStatusCode());
        self::assertSame(Response::HTTP_NOT_FOUND, $my(Request::create('/', 'POST', $this->payload(['catalogProductId' => 9]), [], ['rib' => $this->pdfUpload()]))->getStatusCode());
        self::assertSame(Response::HTTP_CREATED, $my(Request::create('/', 'POST', $this->payload(['catalogProductId' => 10]), [], ['rib' => $this->pdfUpload()]))->getStatusCode());

        $public = new CreatePublicTradeInController($service, $this->validator(1), $products, $tradeInFormatter, new PublicTradeInRateLimiter(new RateLimitKeyFactory(), $this->limiter(10)));
        $public->setContainer($this->controllerContainer(null));
        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $public(Request::create('/', 'POST', $this->payload()))->getStatusCode());
        self::assertSame(Response::HTTP_CREATED, $public(Request::create('/', 'POST', $this->payload(), [], ['rib' => $this->pdfUpload()]))->getStatusCode());

        $publicForUser = new CreatePublicTradeInController($service, $this->validator(1), $products, $tradeInFormatter, new PublicTradeInRateLimiter(new RateLimitKeyFactory(), $this->limiter(10)));
        $publicForUser->setContainer($this->controllerContainer($user));
        self::assertSame(Response::HTTP_CREATED, $publicForUser(Request::create('/', 'POST', $this->payload(), [], ['rib' => $this->pdfUpload()]))->getStatusCode());
    }

    private function limiter(int $limit): \Symfony\Component\RateLimiter\RateLimiterFactory
    {
        return new \Symfony\Component\RateLimiter\RateLimiterFactory([
            'id' => 'test',
            'policy' => 'fixed_window',
            'limit' => $limit,
            'interval' => '1 hour',
        ], new \Symfony\Component\RateLimiter\Storage\InMemoryStorage());
    }
}
