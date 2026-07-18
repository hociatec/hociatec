<?php

declare(strict_types=1);

namespace App\Module\Order\Service;

use App\Module\Order\Entity\Order;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class OrderInvoiceDocumentService
{
    public function __construct(
        private readonly OrderInvoiceCalculator $calculator,
        private readonly OrderInvoicePdfService $pdfService,
        private readonly OrderInvoiceXmlService $xmlService,
        private readonly EntityManagerInterface $em,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
    }

    public function ensureGenerated(Order $order): void
    {
        $this->calculator->snapshot($order);
        $totals = $this->calculator->computeTotals($order);
        $baseName = $order->getInvoiceNumber() ?: $order->getNumber();
        $relativeDirectory = 'public/uploads/invoices';
        $absoluteDirectory = $this->projectDir . '/' . $relativeDirectory;

        if (!is_dir($absoluteDirectory) && !mkdir($absoluteDirectory, 0775, true) && !is_dir($absoluteDirectory)) {
            throw new \RuntimeException('Impossible de créer le répertoire des factures.');
        }

        $pdf = $this->pdfService->render($order, $totals);
        $xml = $this->xmlService->render($order, $totals);
        $pdfRelativePath = 'uploads/invoices/' . $baseName . '.pdf';
        $xmlRelativePath = 'uploads/invoices/' . $baseName . '.xml';

        file_put_contents($this->projectDir . '/public/' . $pdfRelativePath, $pdf);
        file_put_contents($this->projectDir . '/public/' . $xmlRelativePath, $xml);

        $order
            ->setInvoicePdfPath($pdfRelativePath)
            ->setInvoiceXmlPath($xmlRelativePath);

        $this->em->persist($order);
        $this->em->flush();
    }

    public function getPdf(Order $order): string
    {
        if ($order->getInvoicePdfPath() !== null) {
            $absolutePath = $this->projectDir . '/public/' . ltrim($order->getInvoicePdfPath(), '/');
            if (is_file($absolutePath)) {
                $pdf = file_get_contents($absolutePath);
                if ($pdf !== false) {
                    return $pdf;
                }
            }
        }

        $this->ensureGenerated($order);

        $absolutePath = $this->projectDir . '/public/' . ltrim((string) $order->getInvoicePdfPath(), '/');
        $pdf = file_get_contents($absolutePath);
        if ($pdf === false) {
            throw new \RuntimeException('Impossible de lire la facture PDF générée.');
        }

        return $pdf;
    }

    public function getXml(Order $order): string
    {
        if ($order->getInvoiceXmlPath() !== null) {
            $absolutePath = $this->projectDir . '/public/' . ltrim($order->getInvoiceXmlPath(), '/');
            if (is_file($absolutePath)) {
                $xml = file_get_contents($absolutePath);
                if ($xml !== false) {
                    return $xml;
                }
            }
        }

        $this->ensureGenerated($order);

        $absolutePath = $this->projectDir . '/public/' . ltrim((string) $order->getInvoiceXmlPath(), '/');
        $xml = file_get_contents($absolutePath);
        if ($xml === false) {
            throw new \RuntimeException('Impossible de lire la facture XML générée.');
        }

        return $xml;
    }
}
