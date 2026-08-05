<?php

declare(strict_types=1);

namespace App\Module\Order\Infrastructure\Pdf;

use App\Shared\Infrastructure\Pdf\PdfHtmlFormatter;

final readonly class OrderInvoicePdfRows
{
    public function __construct(private PdfHtmlFormatter $formatter)
    {
    }

    /** @param list<array<string,mixed>> $items */
    public function items(array $items): string
    {
        $rows = '';
        foreach ($items as $item) {
            $rows .= sprintf(
                '<tr><td>%s</td><td>%s</td><td class="num">%s</td><td class="num">%s</td><td class="num">%s %%</td><td class="num">%s</td><td class="num">%s</td><td class="num">%s</td></tr>',
                $this->formatter->escape((string) $item['name']),
                $this->formatter->escape((string) $item['sku']),
                (int) $item['quantity'],
                $this->formatter->money((int) $item['unitPriceHtCents']),
                number_format(((int) $item['vatRateBps']) / 100, 2, ',', ' '),
                $this->formatter->money((int) $item['lineSubtotalHtCents']),
                $this->formatter->money((int) $item['lineVatCents']),
                $this->formatter->money((int) $item['lineTotalTtcCents']),
            );
        }

        return $rows;
    }

    /** @param list<array{rateBps:int, taxableCents:int, taxCents:int}> $taxBreakdown */
    public function taxes(array $taxBreakdown): string
    {
        $rows = '';
        foreach ($taxBreakdown as $taxLine) {
            $rows .= sprintf(
                '<tr><td>%s %%</td><td class="num">%s</td><td class="num">%s</td></tr>',
                number_format($taxLine['rateBps'] / 100, 2, ',', ' '),
                $this->formatter->money($taxLine['taxableCents']),
                $this->formatter->money($taxLine['taxCents']),
            );
        }

        return $rows;
    }
}
