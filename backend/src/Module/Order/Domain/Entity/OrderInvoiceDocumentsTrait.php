<?php

declare(strict_types=1);

namespace App\Module\Order\Domain\Entity;

trait OrderInvoiceDocumentsTrait
{
    public function getInvoicePdfPath(): ?string
    {
        return $this->invoice->getPdfPath();
    }

    public function setInvoicePdfPath(?string $invoicePdfPath): self
    {
        $this->invoice->setPdfPath($invoicePdfPath);

        return $this;
    }

    public function getInvoiceXmlPath(): ?string
    {
        return $this->invoice->getXmlPath();
    }

    public function setInvoiceXmlPath(?string $invoiceXmlPath): self
    {
        $this->invoice->setXmlPath($invoiceXmlPath);

        return $this;
    }
}
