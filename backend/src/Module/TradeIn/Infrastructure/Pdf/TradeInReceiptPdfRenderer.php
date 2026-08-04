<?php

declare(strict_types=1);

namespace App\Module\TradeIn\Infrastructure\Pdf;

use App\Module\TradeIn\Application\Port\TradeInReceiptRenderer;
use App\Shared\Infrastructure\Pdf\AccessiblePdfRenderer;

final readonly class TradeInReceiptPdfRenderer implements TradeInReceiptRenderer
{
    public function __construct(private AccessiblePdfRenderer $pdf)
    {
    }

    public function render(string $html): string
    {
        try {
            return $this->pdf->render($html, 'trade-in-receipt', 'Le justificatif de reprise n’a pas pu être généré.');
        } catch (\RuntimeException $exception) {
            if (!class_exists(\Dompdf\Dompdf::class)) {
                throw $exception;
            }

            $dompdf = new \Dompdf\Dompdf(['isRemoteEnabled' => false]);
            $dompdf->loadHtml($html, 'UTF-8');
            $dompdf->setPaper('A4');
            $dompdf->render();

            return $dompdf->output();
        }
    }
}
