<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Admin\Quote;

use App\Module\Admin\UI\Quote\Controller\AddProductItemController;
use App\Module\Admin\UI\Quote\Controller\CreateQuoteController;
use App\Module\Admin\UI\Quote\Controller\DeleteQuoteController;
use App\Module\Admin\UI\Quote\Controller\DuplicateQuoteController;
use App\Module\Admin\UI\Quote\Controller\GeneratePdfController;
use App\Module\Admin\UI\Quote\Controller\ListQuoteMetadataController;
use App\Module\Admin\UI\Quote\Controller\ListQuotesController;
use App\Module\Admin\UI\Quote\Controller\SendQuoteEmailController;
use App\Module\Admin\UI\Quote\Controller\ShowQuoteController;
use App\Module\Admin\UI\Quote\Controller\UpdateQuoteController;
use App\Module\Admin\UI\Quote\Controller\UpdateQuoteStatusController;
use App\Module\Catalog\Infrastructure\Repository\ProductRepository;
use App\Module\Quote\Application\Calculator\QuoteCalculator;
use App\Module\Quote\Application\Workflow\QuoteWorkflowService;
use App\Module\Quote\Domain\Entity\Quote;
use App\Module\Quote\Infrastructure\Pdf\QuotePdfService;
use App\Module\Quote\Infrastructure\Persistence\QuotePersistence;
use App\Shared\Infrastructure\Http\AttachmentResponseFactory;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class AdminQuoteControllersTest extends AdminQuoteIntegrationTestCase
{
    public function testQuoteControllers(): void
    {
        $em = $this->entityManager();
        $quoteRepository = $this->quoteRepository($em);
        $calculator = new QuoteCalculator();
        $quoteService = $this->quoteService($em);
        $emailService = $this->emailService($em);
        $validator = $this->validator(11);
        $quoteFormatter = $this->quoteFormatter();

        $createQuote = new CreateQuoteController($quoteService, $quoteFormatter, $emailService, $validator);
        $createdQuote = $createQuote($this->jsonRequest($this->quotePayload()));
        self::assertSame(Response::HTTP_CREATED, $createdQuote->getStatusCode());
        $quoteId = (int) $this->payload($createdQuote)['data']['id'];
        self::assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, (new CreateQuoteController($this->failingQuoteService($em), $quoteFormatter, $emailService, $validator))($this->jsonRequest($this->quotePayload(['customer' => ['name' => 'Fail']])))->getStatusCode());
        self::assertSame(Response::HTTP_CREATED, (new CreateQuoteController($quoteService, $quoteFormatter, $this->emailService($em, true), $validator))($this->jsonRequest($this->quotePayload(['customer' => ['name' => 'Email fail', 'email' => 'email-fail@example.test']])))->getStatusCode());

        self::assertSame(Response::HTTP_OK, (new ListQuoteMetadataController())()->getStatusCode());
        self::assertSame(Response::HTTP_OK, (new ListQuotesController($quoteRepository, $quoteFormatter))(Request::create('/?q=ada&status=draft'))->getStatusCode());
        self::assertSame(Response::HTTP_NOT_FOUND, (new ShowQuoteController($quoteRepository, $quoteFormatter))(999)->getStatusCode());
        self::assertSame(Response::HTTP_OK, (new ShowQuoteController($quoteRepository, $quoteFormatter))($quoteId)->getStatusCode());

        $updateQuote = new UpdateQuoteController($quoteRepository, $quoteService, $quoteFormatter, $emailService, $validator);
        self::assertSame(Response::HTTP_NOT_FOUND, $updateQuote($this->jsonRequest($this->quotePayload(), 'PUT'), 999)->getStatusCode());
        self::assertSame(Response::HTTP_OK, $updateQuote($this->jsonRequest($this->quotePayload(['customer' => ['name' => 'Grace', 'email' => 'grace@example.test']]), 'PUT'), $quoteId)->getStatusCode());
        $quoteRepository->find($quoteId)?->setCreatedEmailSentAt(null);
        self::assertSame(Response::HTTP_OK, (new UpdateQuoteController($quoteRepository, $quoteService, $quoteFormatter, $this->emailService($em, true), $validator))($this->jsonRequest($this->quotePayload(['customer' => ['name' => 'No mail', 'email' => 'no-mail@example.test']]), 'PUT'), $quoteId)->getStatusCode());
        self::assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, (new UpdateQuoteController($quoteRepository, $this->failingQuoteService($em), $quoteFormatter, $emailService, $validator))($this->jsonRequest($this->quotePayload(), 'PUT'), $quoteId)->getStatusCode());

        $product = $this->product();
        $productRepository = $this->getMockBuilder(ProductRepository::class)->disableOriginalConstructor()->getMock();
        $productRepository->method('find')->willReturnMap([[1, null], [2, $product]]);
        $addProduct = new AddProductItemController(new QuoteWorkflowService(new QuotePersistence($em)), $quoteRepository, $productRepository, $quoteFormatter, $validator);
        self::assertSame(Response::HTTP_NOT_FOUND, $addProduct($this->jsonRequest(['productId' => 2]), 999)->getStatusCode());
        self::assertSame(Response::HTTP_NOT_FOUND, $addProduct($this->jsonRequest(['productId' => 1]), $quoteId)->getStatusCode());
        self::assertSame(Response::HTTP_OK, $addProduct($this->jsonRequest(['productId' => 2, 'quantity' => 2, 'vatRate' => 20]), $quoteId)->getStatusCode());

        $status = new UpdateQuoteStatusController($quoteRepository, $quoteFormatter, new QuoteWorkflowService(new QuotePersistence($em)), $validator);
        self::assertSame(Response::HTTP_NOT_FOUND, $status($this->jsonRequest(['status' => Quote::STATUS_SENT], 'PATCH'), 999)->getStatusCode());
        self::assertSame(Response::HTTP_OK, $status($this->jsonRequest(['status' => Quote::STATUS_SENT], 'PATCH'), $quoteId)->getStatusCode());
        $quote = $quoteRepository->find($quoteId);
        self::assertInstanceOf(Quote::class, $quote);
        $quote->setConvertedOrderId(1)->setConvertedOrderNumber('ADM-ORDER-1');
        self::assertSame(Response::HTTP_BAD_REQUEST, $status($this->jsonRequest(['status' => Quote::STATUS_REFUSED], 'PATCH'), $quoteId)->getStatusCode());
        $quote->setConvertedOrderId(null)->setConvertedOrderNumber(null);

        $send = new SendQuoteEmailController($quoteRepository, $emailService, new QuoteWorkflowService(new QuotePersistence($em)), $this->createMock(LoggerInterface::class), $validator);
        self::assertSame(Response::HTTP_NOT_FOUND, $send($this->jsonRequest(['to' => 'client@example.test']), '0')->getStatusCode());
        self::assertSame(Response::HTTP_NOT_FOUND, $send($this->jsonRequest(['to' => 'client@example.test']), '999')->getStatusCode());
        self::assertSame(Response::HTTP_BAD_REQUEST, $send(Request::create('/', 'POST', server: [], content: '{bad'), (string) $quoteId)->getStatusCode());
        self::assertSame(Response::HTTP_BAD_REQUEST, $send($this->jsonRequest(['to' => 'bad']), (string) $quoteId)->getStatusCode());
        self::assertSame(Response::HTTP_SERVICE_UNAVAILABLE, (new SendQuoteEmailController($quoteRepository, $emailService, new QuoteWorkflowService(new QuotePersistence($em)), $this->createMock(LoggerInterface::class), $this->throwingValidator()))($this->jsonRequest(['to' => 'unexpected@example.test']), (string) $quoteId)->getStatusCode());
        $client = new \App\Module\User\Domain\Entity\User('client@example.test', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $client->setPassword('hashed')->setCommunicationPreferences([]);
        $em->persist($client);
        $em->flush();
        $sentResponse = $send($this->jsonRequest(['to' => 'external-client@example.test']), (string) $quoteId);
        self::assertSame(Response::HTTP_OK, $sentResponse->getStatusCode(), (string) $sentResponse->getContent());

        $pdf = new GeneratePdfController($quoteRepository, $calculator, $this->pdfService(), new AttachmentResponseFactory());
        self::assertSame(Response::HTTP_NOT_FOUND, $pdf(999)->getStatusCode());
        self::assertSame(Response::HTTP_OK, $pdf($quoteId)->getStatusCode());
        self::assertSame(Response::HTTP_NOT_IMPLEMENTED, (new GeneratePdfController($quoteRepository, $calculator, new class extends QuotePdfService {
            public function __construct()
            {
            }

            public function render(Quote $quote, array $totals): string
            {
                throw new \RuntimeException('pdf down');
            }
        }, new AttachmentResponseFactory()))($quoteId)->getStatusCode());

        self::assertSame(Response::HTTP_NOT_FOUND, (new DuplicateQuoteController($quoteRepository, $quoteService, $quoteFormatter))(999)->getStatusCode());
        self::assertSame(Response::HTTP_OK, (new DuplicateQuoteController($quoteRepository, $quoteService, $quoteFormatter))($quoteId)->getStatusCode());
        self::assertSame(Response::HTTP_NOT_FOUND, (new DeleteQuoteController($quoteRepository, $quoteService))(999)->getStatusCode());
        self::assertSame(Response::HTTP_OK, (new DeleteQuoteController($quoteRepository, $quoteService))($quoteId)->getStatusCode());
    }
}
