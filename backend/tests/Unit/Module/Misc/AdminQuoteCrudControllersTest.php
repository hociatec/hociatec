<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Misc;

use App\Module\Admin\UI\Quote\Controller\DeleteQuoteController;
use App\Module\Admin\UI\Quote\Controller\DuplicateQuoteController;
use App\Module\Admin\UI\Quote\Controller\ShowQuoteController;
use App\Module\Quote\Application\Workflow\QuoteService as QuoteDomainService;
use App\Module\Quote\Domain\Entity\Quote;
use App\Module\Quote\Infrastructure\Repository\QuoteRepository;
use Symfony\Component\HttpFoundation\Response;

final class AdminQuoteCrudControllersTest extends MiscSupportTestCase
{
    public function testShowDeleteAndDuplicateQuoteControllers(): void
    {
        $quote = new Quote('Q-1');
        $quote->setCustomerName('Ada')->setCustomerEmail('ada@example.com');
        $this->setId($quote, 5);

        $quotes = $this->createMock(QuoteRepository::class);
        $quotes->expects(self::exactly(4))
            ->method('find')
            ->willReturnOnConsecutiveCalls(null, $quote, null, $quote);

        $show = new ShowQuoteController($quotes, $this->quoteFormatter());
        self::assertSame(Response::HTTP_NOT_FOUND, $show(404)->getStatusCode());
        self::assertSame(Response::HTTP_OK, $show(5)->getStatusCode());

        $service = $this->getMockBuilder(QuoteDomainService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['delete', 'duplicate'])
            ->getMock();
        $service->expects(self::once())->method('delete')->with($quote);
        $copy = new Quote('Q-2');
        $this->setId($copy, 8);
        $service->expects(self::once())->method('duplicate')->with($quote)->willReturn($copy);

        $delete = new DeleteQuoteController($quotes, $service);
        self::assertSame(Response::HTTP_NOT_FOUND, $delete(404)->getStatusCode());
        self::assertSame(Response::HTTP_OK, $delete(5)->getStatusCode());

        $duplicateMissingQuotes = $this->createMock(QuoteRepository::class);
        $duplicateMissingQuotes->expects(self::once())->method('find')->with(404)->willReturn(null);
        $duplicateMissing = new DuplicateQuoteController($duplicateMissingQuotes, $service, $this->quoteFormatter());
        self::assertSame(Response::HTTP_NOT_FOUND, $duplicateMissing(404)->getStatusCode());

        $duplicateQuotes = $this->createMock(QuoteRepository::class);
        $duplicateQuotes->expects(self::once())->method('find')->with(5)->willReturn($quote);
        $duplicate = new DuplicateQuoteController($duplicateQuotes, $service, $this->quoteFormatter());
        $payload = json_decode((string) $duplicate(5)->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('Q-2', $payload['data']['number']);
    }
}
