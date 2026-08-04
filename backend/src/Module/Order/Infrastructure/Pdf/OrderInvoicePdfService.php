<?php

declare(strict_types=1);

namespace App\Module\Order\Infrastructure\Pdf;

use App\Module\Order\Application\Port\OrderInvoicePdfRenderer;
use App\Module\Order\Domain\Entity\Order;
use App\Shared\Infrastructure\Pdf\AccessiblePdfRenderer;
use App\Shared\Infrastructure\Pdf\PdfHtmlFormatter;

final class OrderInvoicePdfService implements OrderInvoicePdfRenderer
{
    private readonly OrderInvoicePdfTemplateRenderer $template;

    public function __construct(
        private readonly AccessiblePdfRenderer $renderer,
        PdfHtmlFormatter $formatter,
        ?OrderInvoicePdfTemplateRenderer $template = null,
    ) {
        $this->template = $template ?? new OrderInvoicePdfTemplateRenderer($formatter);
    }

    /**
     * @param array{
     *   subtotalTtcBeforeDiscount:int,
     *   totalDiscountTtc:int,
     *   totalHt:int,
     *   totalVat:int,
     *   totalTtc:int,
     *   taxBreakdown:list<array{rateBps:int, taxableCents:int, taxCents:int}>,
     *   items:list<array<string,mixed>>
     * } $totals
     */
    public function render(Order $order, array $totals): string
    {
        return $this->renderer->render(
            $this->buildHtml($order, $totals),
            'invoice',
            'Le PDF de facture n\'a pas pu être lu.',
        );
    }

    /**
     * @param array{
     *   subtotalTtcBeforeDiscount:int,
     *   totalDiscountTtc:int,
     *   totalHt:int,
     *   totalVat:int,
     *   totalTtc:int,
     *   taxBreakdown:list<array{rateBps:int, taxableCents:int, taxCents:int}>,
     *   items:list<array<string,mixed>>
     * } $totals
     */
    public function buildHtml(Order $order, array $totals): string
    {
        return $this->template->buildHtml($order, $totals);
    }
}
