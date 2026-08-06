<?php

declare(strict_types=1);

namespace App\Module\Order\Infrastructure\Xml;

use App\Module\Order\Domain\Entity\Order;

final readonly class UblInvoiceDeliveryXml
{
    public function __construct(private UblXmlFormatter $formatter = new UblXmlFormatter())
    {
    }

    public function build(Order $order): string
    {
        if (null === $order->getShippingAddress() || trim((string) $order->getShippingAddress()) === trim((string) $order->getBillingAddress())) {
            return '';
        }

        return sprintf(
            '<cac:DeliveryLocation><cac:Address><cbc:StreetName>%s</cbc:StreetName><cbc:CityName>%s</cbc:CityName><cbc:PostalZone>%s</cbc:PostalZone><cac:Country><cbc:IdentificationCode>FR</cbc:IdentificationCode></cac:Country></cac:Address></cac:DeliveryLocation>',
            $this->formatter->xml((string) $order->getShippingAddress()),
            $this->formatter->xml((string) ($order->getShippingCity() ?? '-')),
            $this->formatter->xml((string) ($order->getShippingPostalCode() ?? '-')),
        );
    }
}
