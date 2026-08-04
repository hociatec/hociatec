<?php

declare(strict_types=1);

namespace App\Module\Quote\Service;

use App\Module\Quote\Entity\Quote;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

final readonly class QuoteEmailDeliveryService
{
    public function __construct(
        private QuoteCalculator $calculator,
        private QuotePdfService $pdfService,
        private MailerInterface $mailer,
        private LoggerInterface $logger,
        private string $mailerFrom,
    ) {
    }

    /**
     * @param array{subject: string, text: string, html: string} $content
     *
     * @return array{to:string, attachmentIncluded:bool, transport:string}
     */
    public function deliver(Quote $quote, string $recipient, array $content): array
    {
        $pdf = $this->generatePdf($quote, $recipient);

        try {
            $this->mailer->send($this->createEmail($quote, $recipient, $content, $pdf));

            return [
                'to' => $recipient,
                'attachmentIncluded' => null !== $pdf,
                'transport' => 'symfony_mailer',
            ];
        } catch (\Exception $exception) {
            $this->logger->error('Quote email send failed.', [
                'quoteId' => $quote->getId(),
                'email' => $recipient,
                'exception' => $exception,
            ]);

            throw new \RuntimeException('Envoi impossible pour le moment. Vérifie la configuration email SMTP.', previous: $exception);
        }
    }

    private function generatePdf(Quote $quote, string $recipient): ?string
    {
        try {
            return $this->pdfService->render($quote, $this->calculator->computeTotals($quote));
        } catch (\Exception $exception) {
            $this->logger->warning('Quote PDF attachment generation failed before email send.', [
                'quoteId' => $quote->getId(),
                'quoteNumber' => $quote->getNumber(),
                'email' => $recipient,
                'exception' => $exception,
            ]);

            return null;
        }
    }

    /** @param array{subject: string, text: string, html: string} $content */
    private function createEmail(Quote $quote, string $recipient, array $content, ?string $pdf): Email
    {
        $recipientName = trim((string) $quote->getCustomerName());
        $email = (new Email())
            ->from(new Address($this->mailerFrom, 'Hociatec'))
            ->to(new Address($recipient, '' !== $recipientName ? $recipientName : $recipient))
            ->subject($content['subject'])
            ->text($content['text'])
            ->html($content['html']);

        if (null !== $pdf) {
            $email->attach($pdf, sprintf('%s.pdf', $quote->getNumber()), 'application/pdf');
        }

        return $email;
    }
}
