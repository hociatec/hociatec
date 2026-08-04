<?php

declare(strict_types=1);

namespace App\Module\Quote\Infrastructure\Pdf;

use App\Module\Quote\Application\Port\QuotePdfRenderer;
use App\Module\Quote\Domain\Entity\Quote;
use App\Shared\Infrastructure\Pdf\AccessiblePdfRenderer;
use App\Shared\Infrastructure\Pdf\PdfHtmlFormatter;

class QuotePdfService implements QuotePdfRenderer
{
    private readonly QuotePdfTemplateRenderer $template;

    public function __construct(
        private readonly AccessiblePdfRenderer $renderer,
        PdfHtmlFormatter $formatter,
        ?QuotePdfTemplateRenderer $template = null,
    ) {
        $this->template = $template ?? new QuotePdfTemplateRenderer($formatter);
    }

    /** @param array{totalHt: int, totalVat: int, totalTtc: int} $totals */
    public function render(Quote $quote, array $totals): string
    {
        return $this->renderer->render(
            $this->buildHtml($quote, $totals),
            'quote',
            'Le PDF accessible du devis n\'a pas pu être lu.',
        );
    }

    /** @param array{totalHt: int, totalVat: int, totalTtc: int} $totals */
    public function buildHtml(Quote $quote, array $totals): string
    {
        return $this->template->buildHtml($quote, $totals);
    }
}
