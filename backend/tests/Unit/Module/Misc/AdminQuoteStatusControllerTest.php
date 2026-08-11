<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Misc;

use App\Module\Admin\UI\Quote\Controller\UpdateQuoteStatusController;
use App\Module\Quote\Application\Workflow\QuoteWorkflowService;
use App\Module\Quote\Domain\Entity\Quote;
use App\Module\Quote\Infrastructure\Persistence\QuotePersistence;
use App\Module\Quote\Infrastructure\Repository\QuoteRepository;
use App\Shared\Infrastructure\Validation\ConstraintViolationFormatter;
use App\Shared\Infrastructure\Validation\DtoValidator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validation;

final class AdminQuoteStatusControllerTest extends MiscSupportTestCase
{
    public function testUpdateQuoteStatusController(): void
    {
        $quote = new Quote('Q-9');
        $quote->setStatus(Quote::STATUS_DRAFT);
        $this->setId($quote, 9);

        $quotes = $this->createMock(QuoteRepository::class);
        $quotes->expects(self::exactly(2))
            ->method('find')
            ->willReturnOnConsecutiveCalls(null, $quote);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('flush');
        $workflow = new QuoteWorkflowService(new QuotePersistence($entityManager));
        $validator = new DtoValidator(
            Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator(),
            new ConstraintViolationFormatter(),
        );

        $controller = new UpdateQuoteStatusController($quotes, $this->quoteFormatter(), $workflow, $validator);
        self::assertSame(Response::HTTP_NOT_FOUND, $controller(new Request(content: '{"status":"sent"}'), 404)->getStatusCode());

        $payload = json_decode((string) $controller(new Request(content: '{"status":"envoyé"}'), 9)->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('sent', $payload['data']['statusCode']);
        self::assertSame('envoyé', $payload['data']['statusLabel']);
        self::assertInstanceOf(\DateTimeImmutable::class, $quote->getCreatedEmailSentAt());

        $convertedQuote = new Quote('Q-10');
        $convertedQuote->setStatus(Quote::STATUS_ACCEPTED)->setConvertedOrderId(1)->setConvertedOrderNumber('ORD-1');
        $this->setId($convertedQuote, 10);

        $quotes2 = $this->createMock(QuoteRepository::class);
        $quotes2->expects(self::once())->method('find')->with(10)->willReturn($convertedQuote);
        $entityManager2 = $this->createMock(EntityManagerInterface::class);
        $entityManager2->expects(self::never())->method('flush');
        $controller2 = new UpdateQuoteStatusController(
            $quotes2,
            $this->quoteFormatter(),
            new QuoteWorkflowService(new QuotePersistence($entityManager2)),
            $validator,
        );
        self::assertSame(Response::HTTP_BAD_REQUEST, $controller2(new Request(content: '{"status":"refused"}'), 10)->getStatusCode());
    }
}
