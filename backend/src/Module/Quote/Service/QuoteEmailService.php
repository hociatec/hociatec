<?php

declare(strict_types=1);

namespace App\Module\Quote\Service;

use App\Module\Quote\Entity\Quote;
use App\Shared\Http\OvhRoundcubeMailer;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

final class QuoteEmailService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly QuoteCalculator $calculator,
        private readonly QuotePdfService $pdfService,
        private readonly MailerInterface $mailer,
        private readonly OvhRoundcubeMailer $ovhRoundcubeMailer,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @return array{to: string, attachmentIncluded: bool, transport: string}
     */
    public function send(Quote $quote, ?string $overrideRecipient = null): array
    {
        return $this->sendCreated($quote, $overrideRecipient);
    }

    public function sendCreatedIfNeeded(Quote $quote): bool
    {
        if ($quote->getCreatedEmailSentAt() !== null) {
            return false;
        }

        $this->sendCreated($quote);
        $quote->setCreatedEmailSentAt(new \DateTimeImmutable());
        $this->entityManager->flush();

        return true;
    }

    public function sendCreated(Quote $quote, ?string $overrideRecipient = null): array
    {
        $recipient = $this->resolveRecipient($quote, $overrideRecipient);
        $content = $this->buildCreatedContent($quote);

        return $this->deliver($quote, $recipient, $content['subject'], $content['text'], $content['html']);
    }

    private function resolveRecipient(Quote $quote, ?string $overrideRecipient): string
    {
        $recipient = trim((string) ($overrideRecipient ?? $quote->getCustomerEmail()));
        if ($recipient === '') {
            throw new \InvalidArgumentException('Aucune adresse e-mail destinataire n’est renseignée pour ce devis.');
        }

        if (filter_var($recipient, FILTER_VALIDATE_EMAIL) === false) {
            throw new \InvalidArgumentException('L’adresse e-mail du destinataire est invalide.');
        }

        return $recipient;
    }

    /**
     * @return array{subject: string, text: string, html: string}
     */
    private function buildCreatedContent(Quote $quote): array
    {
        $totals = $this->calculator->computeTotals($quote);
        $customerName = trim((string) $quote->getCustomerName()) !== '' ? trim((string) $quote->getCustomerName()) : 'Bonjour';
        $validUntil = $quote->getValidUntil()?->format('d/m/Y');
        $totalTtc = number_format($totals['totalTtc'] / 100, 2, ',', ' ');
        $frontendUrl = rtrim((string) ($_ENV['APP_FRONTEND_URL'] ?? 'http://localhost:5173'), '/');
        $quoteUrl = $frontendUrl . '/quotes/me/' . $quote->getId();
        $subject = sprintf('Votre devis %s a bien été créé', $quote->getNumber());

        $lines = [
            sprintf('Bonjour %s,', $customerName),
            '',
            'Votre devis a bien été créé par Hociatec.',
            sprintf('Référence du devis : %s.', $quote->getNumber()),
            sprintf('Montant total TTC : %s EUR.', $totalTtc),
        ];

        if ($validUntil !== null) {
            $lines[] = sprintf('Date de validité : %s.', $validUntil);
        }

        $lines[] = sprintf('Vous pouvez le consulter depuis votre espace client : %s', $quoteUrl);
        $lines[] = '';
        $lines[] = 'Pensez à vérifier les éléments du devis et à revenir vers nous si vous souhaitez un ajustement.';
        $lines[] = 'Nous restons à votre disposition pour toute question.';
        $lines[] = '';
        $lines[] = 'Cordialement,';
        $lines[] = 'L’équipe Hociatec';
        $lines[] = (string) ($_ENV['MAILER_FROM'] ?? 'contact@hociatec.fr');

        $text = implode("\n", $lines);
        $html = implode('', [
            '<p>Bonjour ' . htmlspecialchars($customerName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . ',</p>',
            '<p>Votre devis a bien été créé par <strong>Hociatec</strong>.</p>',
            '<p>Référence du devis : <strong>' . htmlspecialchars($quote->getNumber(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</strong>.</p>',
            '<p>Montant total TTC : <strong>' . htmlspecialchars($totalTtc, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . ' EUR</strong>.</p>',
            $validUntil !== null
                ? '<p>Date de validité : <strong>' . htmlspecialchars($validUntil, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</strong>.</p>'
                : '',
            '<p>Vous pouvez le consulter depuis votre espace client : <a href="' . htmlspecialchars($quoteUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '">' . htmlspecialchars($quoteUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</a></p>',
            '<p>Pensez à vérifier les éléments du devis et à revenir vers nous si vous souhaitez un ajustement.</p>',
            '<p>Nous restons à votre disposition pour toute question.</p>',
            '<p>Cordialement,<br>L’équipe Hociatec<br>' . htmlspecialchars((string) ($_ENV['MAILER_FROM'] ?? 'contact@hociatec.fr'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</p>',
        ]);

        return [
            'subject' => $subject,
            'text' => $text,
            'html' => $html,
        ];
    }

    private function createEmail(
        Quote $quote,
        string $recipient,
        string $subject,
        string $text,
        string $html,
        ?string $pdf,
    ): Email {
        $from = (string) ($_ENV['MAILER_FROM'] ?? 'no-reply@localhost');
        $recipientName = trim((string) $quote->getCustomerName());

        $email = (new Email())
            ->from(new Address($from, 'Hociatec'))
            ->to(new Address($recipient, $recipientName !== '' ? $recipientName : $recipient))
            ->subject($subject)
            ->text($text)
            ->html($html);

        if ($pdf !== null) {
            $email->attach($pdf, sprintf('%s.pdf', $quote->getNumber()), 'application/pdf');
        }

        return $email;
    }

    /**
     * @return array{to: string, attachmentIncluded: bool, transport: string}
     */
    private function deliver(
        Quote $quote,
        string $recipient,
        string $subject,
        string $text,
        string $html,
    ): array {
        $pdf = null;
        try {
            $pdf = $this->pdfService->render($quote, $this->calculator->computeTotals($quote));
        } catch (\Throwable $exception) {
            $this->logger->warning('Quote PDF attachment generation failed before email send.', [
                'quoteId' => $quote->getId(),
                'quoteNumber' => $quote->getNumber(),
                'email' => $recipient,
                'exception' => $exception,
            ]);
        }

        if ($pdf === null) {
            try {
                $this->ovhRoundcubeMailer->send($recipient, $subject, $text);

                return [
                    'to' => $recipient,
                    'attachmentIncluded' => false,
                    'transport' => 'ovh_roundcube',
                ];
            } catch (\Throwable $exception) {
                $this->logger->warning('Quote email Roundcube primary transport failed.', [
                    'quoteId' => $quote->getId(),
                    'quoteNumber' => $quote->getNumber(),
                    'email' => $recipient,
                    'exception' => $exception,
                ]);
            }
        }

        try {
            $email = $this->createEmail($quote, $recipient, $subject, $text, $html, $pdf);
            $this->mailer->send($email);

            return [
                'to' => $recipient,
                'attachmentIncluded' => $pdf !== null,
                'transport' => 'symfony_mailer',
            ];
        } catch (\Throwable $exception) {
            if ($pdf !== null) {
                try {
                    $this->ovhRoundcubeMailer->send($recipient, $subject, $text);

                    $this->logger->warning('Quote email sent without attachment after SMTP failure.', [
                        'quoteId' => $quote->getId(),
                        'quoteNumber' => $quote->getNumber(),
                        'email' => $recipient,
                        'exception' => $exception,
                    ]);

                    return [
                        'to' => $recipient,
                        'attachmentIncluded' => false,
                        'transport' => 'ovh_roundcube_fallback',
                    ];
                } catch (\Throwable $fallbackException) {
                    $this->logger->error('Quote email fallback transport failed.', [
                        'quoteId' => $quote->getId(),
                        'quoteNumber' => $quote->getNumber(),
                        'email' => $recipient,
                        'exception' => $fallbackException,
                        'previousException' => $exception,
                    ]);
                }
            } else {
                $this->logger->error('Quote email send failed.', [
                    'quoteId' => $quote->getId(),
                    'quoteNumber' => $quote->getNumber(),
                    'email' => $recipient,
                    'exception' => $exception,
                ]);
            }

            throw new \RuntimeException('Envoi impossible pour le moment. Vérifie la configuration email SMTP ou OVH.');
        }
    }
}
