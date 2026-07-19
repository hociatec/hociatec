<?php

declare(strict_types=1);

namespace App\Module\Order\Service;

use App\Module\Marketing\Repository\EmailTemplateRepository;
use App\Module\Order\Entity\Order;
use App\Module\Quote\Repository\QuoteRepository;
use App\Shared\Http\OvhRoundcubeMailer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

final class OrderNotificationEmailService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly EmailTemplateRepository $templates,
        private readonly MailerInterface $mailer,
        private readonly OvhRoundcubeMailer $ovhRoundcubeMailer,
        private readonly OrderEventLogger $events,
        private readonly QuoteRepository $quotes,
    ) {
    }

    public function sendOrderCreatedIfNeeded(Order $order): bool
    {
        return $this->sendOrderCreated($order, false);
    }

    public function sendInvoiceIssuedIfNeeded(Order $order): bool
    {
        return $this->sendInvoiceIssued($order, false);
    }

    public function sendStatusChangedIfNeeded(Order $order, string $oldStatus, string $newStatus): bool
    {
        return $this->sendStatusChanged($order, $oldStatus, $newStatus, false);
    }

    public function resendOrderCreated(Order $order): bool
    {
        return $this->sendOrderCreated($order, true);
    }

    public function resendInvoiceIssued(Order $order): bool
    {
        return $this->sendInvoiceIssued($order, true);
    }

    public function resendStatusChanged(Order $order, string $oldStatus, string $newStatus): bool
    {
        return $this->sendStatusChanged($order, $oldStatus, $newStatus, true);
    }

    private function sendOrderCreated(Order $order, bool $force): bool
    {
        if (!$force && $order->getOrderCreatedEmailSentAt() !== null) {
            return false;
        }

        $this->sendScenario($order, 'order_created');
        $order->setOrderCreatedEmailSentAt(new \DateTimeImmutable());
        $this->entityManager->flush();
        $this->events->log($order, null, 'email_sent', $force ? 'Email client renvoyé: commande enregistrée.' : 'Email client envoyé: commande enregistrée.');

        return true;
    }

    private function sendInvoiceIssued(Order $order, bool $force): bool
    {
        if (
            (!$force && $order->getInvoiceEmailSentAt() !== null)
            || $order->getInvoicePdfPath() === null
            || $order->getInvoiceXmlPath() === null
        ) {
            return false;
        }

        $this->sendScenario($order, 'order_invoice_issued');
        $order->setInvoiceEmailSentAt(new \DateTimeImmutable());
        $this->entityManager->flush();
        $this->events->log($order, null, $force ? 'email_resent' : 'email_sent', $force ? 'Email client renvoyé: facture disponible.' : 'Email client envoyé: facture disponible.');

        return true;
    }

    private function sendStatusChanged(Order $order, string $oldStatus, string $newStatus, bool $force): bool
    {
        $scenarioKey = match ($newStatus) {
            Order::STATUS_DELIVERED => 'order_status_delivered',
            Order::STATUS_CANCELLED => 'order_status_cancelled',
            default => null,
        };

        if ($scenarioKey === null || (!$force && $this->hasStatusNotificationAlreadyBeenSent($order, $newStatus))) {
            return false;
        }

        $this->sendScenario($order, $scenarioKey, [
            'previous_order_status' => $oldStatus,
            'previous_order_status_label' => $this->formatStatus($oldStatus),
        ]);

        $sentAt = new \DateTimeImmutable();
        match ($newStatus) {
            Order::STATUS_CONFIRMED => $order->setStatusConfirmedEmailSentAt($sentAt),
            Order::STATUS_DELIVERED => $order->setStatusDeliveredEmailSentAt($sentAt),
            Order::STATUS_CANCELLED => $order->setStatusCancelledEmailSentAt($sentAt),
            default => null,
        };

        $this->entityManager->flush();
        $this->events->log($order, null, $force ? 'email_resent' : 'email_sent', ($force ? 'Email client renvoyé: statut ' : 'Email client envoyé: statut ') . $this->formatStatus($newStatus) . '.');

        return true;
    }

    /**
     * @param array<string, string> $extraContext
     */
    private function sendScenario(Order $order, string $scenarioKey, array $extraContext = []): void
    {
        $template = $this->templates->findActiveOneByScenarioKey($scenarioKey);
        $context = $this->buildContext($order, $extraContext);
        $fallback = $this->fallbackTemplate($scenarioKey);

        $subject = $template?->getSubjectTemplate() ?? $fallback['subject'];
        $htmlBody = $template?->getHtmlBody() ?? $fallback['html'];
        $textBody = $template?->getTextBody() ?? $fallback['text'];

        $renderedSubject = $this->renderTemplate($subject, $context, false);
        $renderedHtml = $this->renderTemplate($htmlBody, $context, true);
        $renderedText = $this->renderTemplate($textBody, $context, false);

        $from = $_ENV['MAILER_FROM'] ?? 'no-reply@localhost';

        try {
            $this->ovhRoundcubeMailer->send(
                $order->getUser()->getEmail(),
                $renderedSubject,
                $renderedText,
            );
        } catch (\Throwable) {
            $email = (new Email())
                ->from(new Address($from, 'Hociatec'))
                ->to(new Address($order->getUser()->getEmail(), $order->getUser()->getFullName()))
                ->subject($renderedSubject)
                ->html($renderedHtml)
                ->text($renderedText);

            $this->mailer->send($email);
        }
    }

    private function hasStatusNotificationAlreadyBeenSent(Order $order, string $newStatus): bool
    {
        return match ($newStatus) {
            Order::STATUS_DELIVERED => $order->getStatusDeliveredEmailSentAt() !== null,
            Order::STATUS_CANCELLED => $order->getStatusCancelledEmailSentAt() !== null,
            default => true,
        };
    }

    /**
     * @param array<string, string> $extraContext
     * @return array<string, string>
     */
    private function buildContext(Order $order, array $extraContext = []): array
    {
        $frontendUrl = rtrim((string) ($_ENV['APP_FRONTEND_URL'] ?? 'http://localhost:5173'), '/');
        $invoiceDate = $order->getInvoicedAt()?->format('d/m/Y') ?? '';
        $invoiceNumber = $order->getInvoiceNumber() ?? '';
        $sourceQuote = $this->quotes->findOneBy(['convertedOrder' => $order]);
        $quoteNumber = $sourceQuote?->getNumber() ?? '';
        $isPendingPayment = $order->getStatus() === Order::STATUS_PENDING;

        return $extraContext + [
            'first_name' => $order->getUser()->getFirstName(),
            'last_name' => $order->getUser()->getLastName(),
            'full_name' => $order->getUser()->getFullName(),
            'email' => $order->getUser()->getEmail(),
            'order_number' => $order->getNumber(),
            'order_status' => $order->getStatus(),
            'order_status_label' => $this->formatStatus($order->getStatus()),
            'order_email_status_title' => $isPendingPayment ? 'en attente de règlement' : 'confirmée',
            'order_payment_instruction' => $isPendingPayment
                ? 'Cette commande est en attente de règlement. Elle sera validée et confirmée uniquement après réception effective du paiement.'
                : 'Cette commande est confirmée. Vous pouvez suivre sa préparation depuis votre espace client.',
            'order_payment_next_step' => $isPendingPayment
                ? 'Pour finaliser la commande, ouvrez le lien ci-dessous puis cliquez sur le bouton de règlement. Une fois le paiement accepté, la commande passera automatiquement au statut confirmé.'
                : 'Aucune action de paiement supplémentaire n’est nécessaire pour cette commande.',
            'quote_number' => $quoteNumber,
            'order_origin_sentence' => $quoteNumber !== ''
                ? 'Cette commande résulte de votre devis numéro ' . $quoteNumber . '. Les lignes, quantités et montants repris correspondent au devis accepté.'
                : 'Cette commande a été enregistrée depuis votre espace client.',
            'invoice_number' => $invoiceNumber,
            'invoice_date' => $invoiceDate,
            'order_total_eur' => number_format($order->getTotalPriceCents() / 100, 2, ',', ' '),
            'order_created_at' => $order->getCreatedAt()->format('d/m/Y'),
            'billing_name' => (string) ($order->getBillingName() ?? $order->getUser()->getFullName()),
            'app_frontend_url' => $frontendUrl,
            'order_detail_url' => $frontendUrl . '/orders/' . $order->getId(),
            'orders_list_url' => $frontendUrl . '/orders/me',
            'invoice_pdf_url' => $frontendUrl . '/orders/' . $order->getId(),
            'invoice_xml_url' => $frontendUrl . '/orders/' . $order->getId(),
            'purchase_order_number' => (string) ($order->getPurchaseOrderNumber() ?? ''),
        ];
    }

    /**
     * @return array{subject: string, html: string, text: string}
     */
    private function fallbackTemplate(string $scenarioKey): array
    {
        return match ($scenarioKey) {
            'order_created' => [
                'subject' => 'Commande {{order_number}} {{order_email_status_title}}',
                'html' => '<p>Bonjour {{first_name}},</p><p>Votre commande <strong>{{order_number}}</strong> a bien été enregistrée pour un montant total de <strong>{{order_total_eur}} EUR</strong>.</p><p>{{order_origin_sentence}}</p><p>{{order_payment_instruction}}</p><p>{{order_payment_next_step}}</p><p>Accès commande : <a href="{{order_detail_url}}">{{order_detail_url}}</a></p>',
                'text' => "Bonjour {{first_name}},\n\nVotre commande {{order_number}} a bien été enregistrée pour un montant total de {{order_total_eur}} EUR.\n\n{{order_origin_sentence}}\n\n{{order_payment_instruction}}\n\n{{order_payment_next_step}}\n\nAccès commande : {{order_detail_url}}",
            ],
            'order_invoice_issued' => [
                'subject' => 'Votre facture {{invoice_number}} est disponible',
                'html' => '<p>Bonjour {{first_name}},</p><p>Votre facture <strong>{{invoice_number}}</strong> du {{invoice_date}} est maintenant disponible.</p><p>Retrouvez-la depuis le détail de votre commande : <a href="{{order_detail_url}}">{{order_detail_url}}</a></p>',
                'text' => "Bonjour {{first_name}},\n\nVotre facture {{invoice_number}} du {{invoice_date}} est maintenant disponible.\n\nAccès commande : {{order_detail_url}}",
            ],
            'order_status_delivered' => [
                'subject' => 'Commande {{order_number}} livrée',
                'html' => '<p>Bonjour {{first_name}},</p><p>Votre commande <strong>{{order_number}}</strong> est marquée comme <strong>{{order_status_label}}</strong>.</p><p>Consultez votre espace client : <a href="{{order_detail_url}}">{{order_detail_url}}</a></p>',
                'text' => "Bonjour {{first_name}},\n\nVotre commande {{order_number}} est marquée comme {{order_status_label}}.\n\nDétail : {{order_detail_url}}",
            ],
            'order_status_cancelled' => [
                'subject' => 'Commande {{order_number}} annulée',
                'html' => '<p>Bonjour {{first_name}},</p><p>Votre commande <strong>{{order_number}}</strong> est désormais <strong>{{order_status_label}}</strong>.</p><p>Consultez le détail : <a href="{{order_detail_url}}">{{order_detail_url}}</a></p>',
                'text' => "Bonjour {{first_name}},\n\nVotre commande {{order_number}} est désormais {{order_status_label}}.\n\nDétail : {{order_detail_url}}",
            ],
            default => [
                'subject' => 'Mise à jour de votre commande {{order_number}}',
                'html' => '<p>Bonjour {{first_name}},</p><p>Votre commande <strong>{{order_number}}</strong> a été mise à jour.</p>',
                'text' => "Bonjour {{first_name}},\n\nVotre commande {{order_number}} a été mise à jour.",
            ],
        };
    }

    /**
     * @param array<string, string> $context
     */
    private function renderTemplate(?string $template, array $context, bool $allowHtml): string
    {
        $template = (string) $template;
        $replacements = [];

        foreach ($context as $key => $value) {
            $replacements['{{' . $key . '}}'] = $allowHtml
                ? htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                : $value;
        }

        return strtr($template, $replacements);
    }

    private function formatStatus(string $status): string
    {
        return match ($status) {
            Order::STATUS_PENDING => 'en attente',
            Order::STATUS_CONFIRMED => 'confirmée',
            Order::STATUS_DELIVERED => 'livrée',
            Order::STATUS_CANCELLED => 'annulée',
            default => $status,
        };
    }
}
