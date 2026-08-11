<?php

declare(strict_types=1);

namespace App\Module\Order\Application\Workflow;

use App\Module\Order\Application\Calculator\OrderInvoiceCalculator;
use App\Module\Order\Application\DTO\InvoiceDocument;
use App\Module\Order\Application\Port\OrderInvoicePdfRenderer;
use App\Module\Order\Domain\Entity\Order;
use App\Shared\Application\UnitOfWork;

final class OrderInvoiceDocumentService
{
    public function __construct(
        private readonly OrderInvoiceCalculator $calculator,
        private readonly OrderInvoicePdfRenderer $pdfService,
        private readonly OrderInvoiceXmlService $xmlService,
        private readonly UnitOfWork $persistence,
        private readonly OrderInvoicePrivateDocumentStorage $storage,
    ) {
    }

    public function ensureGenerated(Order $order): void
    {
        if ($this->storage->documentsExist($order->getInvoicePdfPath(), $order->getInvoiceXmlPath())) {
            return;
        }

        $this->calculator->snapshot($order);
        $totals = $this->calculator->computeTotals($order);
        $baseName = $order->getInvoiceNumber() ?: $order->getNumber();
        $document = new InvoiceDocument(
            $this->pdfService->render($order, $totals),
            $this->xmlService->render($order, $totals),
        );
        $paths = $this->storage->save($baseName, $document);

        $order
            ->setInvoicePdfPath($paths['pdf'])
            ->setInvoiceXmlPath($paths['xml']);

        $this->persistence->persist($order);
        $this->persistence->flush();
    }

    public function getPdf(Order $order): string
    {
        if ($this->storage->canRead($order->getInvoicePdfPath())) {
            return $this->storage->readPdf((string) $order->getInvoicePdfPath());
        }

        $this->ensureGenerated($order);

        return $this->storage->readPdf((string) $order->getInvoicePdfPath());
    }

    public function getXml(Order $order): string
    {
        if ($this->storage->canRead($order->getInvoiceXmlPath())) {
            return $this->storage->readXml((string) $order->getInvoiceXmlPath());
        }

        $this->ensureGenerated($order);

        return $this->storage->readXml((string) $order->getInvoiceXmlPath());
    }
}
