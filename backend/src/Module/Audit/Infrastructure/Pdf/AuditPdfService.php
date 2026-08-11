<?php

declare(strict_types=1);

namespace App\Module\Audit\Infrastructure\Pdf;

use App\Module\Audit\Application\Port\AuditPdfRenderer;
use App\Module\Audit\Domain\Entity\AuditRequest;

class AuditPdfService implements AuditPdfRenderer
{
    public function __construct(private readonly AuditPdfHtmlBuilder $html = new AuditPdfHtmlBuilder())
    {
    }

    public function renderDetailed(AuditRequest $audit): string
    {
        if (!class_exists(\Dompdf\Dompdf::class)) {
            throw new \RuntimeException('Dompdf non installe');
        }

        return $this->renderHtml($this->html->detailed($audit));
    }

    public function renderSummary(AuditRequest $audit): string
    {
        if (!class_exists(\Dompdf\Dompdf::class)) {
            throw new \RuntimeException('Dompdf non installe');
        }

        return $this->renderHtml($this->html->summary($audit));
    }

    private function renderHtml(string $html): string
    {
        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }
}
