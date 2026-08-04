<?php

declare(strict_types=1);

namespace App\Module\Order\Domain\Entity;

use Doctrine\ORM\Mapping as ORM;

trait OrderInvoiceDocumentsTrait
{
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $invoicePdfPath = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $invoiceXmlPath = null;

    public function getInvoicePdfPath(): ?string
    {
        return $this->invoicePdfPath;
    }

    public function setInvoicePdfPath(?string $invoicePdfPath): self
    {
        $this->invoicePdfPath = $invoicePdfPath;

        return $this;
    }

    public function getInvoiceXmlPath(): ?string
    {
        return $this->invoiceXmlPath;
    }

    public function setInvoiceXmlPath(?string $invoiceXmlPath): self
    {
        $this->invoiceXmlPath = $invoiceXmlPath;

        return $this;
    }
}
