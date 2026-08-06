<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Quote\Service;

use App\Module\Quote\Application\Calculator\QuoteCalculator;
use App\Module\Quote\Application\Workflow\QuoteEmailDeliveryService;
use App\Module\Quote\Domain\Entity\Quote;
use App\Module\Quote\Domain\Entity\QuoteItem;
use App\Module\Quote\Infrastructure\Pdf\QuotePdfService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use App\Shared\Application\Mail\EmailSender;
use Symfony\Component\Mime\Email;

final class QuoteEmailDeliveryServiceTest extends TestCase
{
    public function testDeliverSendsEmailWithAttachmentWhenPdfIsAvailable(): void
    {
        $calculator = $this->createMock(QuoteCalculator::class);
        $calculator->method('computeTotals')->willReturn([
            'subtotalCents' => 10000,
            'discountCents' => 0,
            'totalCents' => 10000,
        ]);

        $pdf = $this->createMock(QuotePdfService::class);
        $pdf->method('render')->willReturn('%PDF-1.4');

        $mailer = $this->createMock(EmailSender::class);
        $mailer->expects(self::once())
            ->method('send')
            ->with(self::callback(static function (Email $email): bool {
                return 'Devis DEV-1' === $email->getSubject()
                    && null !== $email->getAttachments()
                    && str_contains($email->getHtmlBody() ?? '', 'Voir votre devis');
            }));

        $service = new QuoteEmailDeliveryService(
            $calculator,
            $pdf,
            $mailer,
            $this->createMock(LoggerInterface::class),
            'noreply@example.com',
        );

        $result = $service->deliver($this->quote(), 'client@example.com', [
            'subject' => 'Devis DEV-1',
            'text' => 'Voir votre devis',
            'html' => '<p>Voir votre devis</p>',
        ]);

        self::assertSame([
            'to' => 'client@example.com',
            'attachmentIncluded' => true,
            'transport' => 'symfony_mailer',
        ], $result);
    }

    public function testDeliverFallsBackToMailWithoutAttachmentWhenPdfGenerationFailsAndThrowsOnMailerFailure(): void
    {
        $calculator = $this->createMock(QuoteCalculator::class);
        $calculator->method('computeTotals')->willReturn([
            'subtotalCents' => 10000,
            'discountCents' => 0,
            'totalCents' => 10000,
        ]);

        $pdf = $this->createMock(QuotePdfService::class);
        $pdf->method('render')->willThrowException(new \RuntimeException('pdf down'));
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('warning');

        $mailer = $this->createMock(EmailSender::class);
        $mailer->expects(self::once())
            ->method('send')
            ->with(self::callback(static function (Email $email): bool {
                return [] === $email->getAttachments();
            }));

        $service = new QuoteEmailDeliveryService(
            $calculator,
            $pdf,
            $mailer,
            $logger,
            'noreply@example.com',
        );

        $result = $service->deliver($this->quote(), 'client@example.com', [
            'subject' => 'Devis DEV-1',
            'text' => 'Voir votre devis',
            'html' => '<p>Voir votre devis</p>',
        ]);
        self::assertFalse($result['attachmentIncluded']);

        $mailer2 = $this->createMock(EmailSender::class);
        $mailer2->method('send')->willThrowException(new \RuntimeException('smtp down'));
        $logger2 = $this->createMock(LoggerInterface::class);
        $logger2->expects(self::once())->method('warning');
        $logger2->expects(self::once())->method('error');
        $service2 = new QuoteEmailDeliveryService(
            $calculator,
            $pdf,
            $mailer2,
            $logger2,
            'noreply@example.com',
        );

        try {
            $service2->deliver($this->quote(), 'client@example.com', [
                'subject' => 'Devis DEV-1',
                'text' => 'Voir votre devis',
                'html' => '<p>Voir votre devis</p>',
            ]);
            self::fail('Expected runtime exception.');
        } catch (\RuntimeException $exception) {
            self::assertSame('Envoi impossible pour le moment. Vérifie la configuration email SMTP.', $exception->getMessage());
            self::assertInstanceOf(\RuntimeException::class, $exception->getPrevious());
        }
    }

    private function quote(): Quote
    {
        $quote = new Quote('DEV-1', 'Ada Lovelace', 'client@example.com');
        $item = new QuoteItem('Phone', 10000);
        $item->setQuantity(1);
        $quote->addItem($item);

        return $quote;
    }
}
