<?php

declare(strict_types=1);

namespace App\Module\Order\Infrastructure\Pdf;

use App\Module\Order\Domain\Entity\Order;
use App\Shared\Infrastructure\Pdf\PdfHtmlFormatter;

final readonly class OrderInvoicePdfCustomerBlock
{
    public function __construct(private PdfHtmlFormatter $formatter)
    {
    }

    /** @return array<string, string> */
    public function build(Order $order): array
    {
        $customerDisplayName = $this->resolveCustomerName($order);
        $customerCity = trim(sprintf('%s %s', (string) $order->getBillingPostalCode(), (string) $order->getBillingCity()));

        return [
            'name' => '' !== $customerDisplayName ? $this->formatter->escape($customerDisplayName) : '-',
            'company' => $order->getBillingCompany() ? '<p>Société : '.$this->formatter->escape($order->getBillingCompany()).'</p>' : '<p>Client : particulier</p>',
            'siren' => $order->getBillingCompanySiren() ? '<p>SIREN client : '.$this->formatter->escape($order->getBillingCompanySiren()).'</p>' : '',
            'vatNumber' => $order->getBillingCompanyVatNumber() ? '<p>TVA client : '.$this->formatter->escape($order->getBillingCompanyVatNumber()).'</p>' : '',
            'address' => $order->getBillingAddress() ? $this->formatter->paragraphsFromLines($order->getBillingAddress()) : '',
            'city' => '' !== $customerCity ? '<p>'.$this->formatter->escape($customerCity).'</p>' : '',
            'email' => $order->getBillingEmail() ? '<p>Email : '.$this->formatter->escape($order->getBillingEmail()).'</p>' : '',
            'phone' => '' !== trim($order->getUser()->getPhoneNumber()) ? '<p>Téléphone : '.$this->formatter->escape($order->getUser()->getPhoneNumber()).'</p>' : '',
            'delivery' => $this->deliveryHtml($order),
        ];
    }

    private function resolveCustomerName(Order $order): string
    {
        $billingName = trim((string) $order->getBillingName());

        return '' !== $billingName ? $billingName : trim($order->getUser()->getFirstName().' '.$order->getUser()->getLastName());
    }

    private function deliveryHtml(Order $order): string
    {
        if (null === $order->getShippingAddress() || trim((string) $order->getShippingAddress()) === trim((string) $order->getBillingAddress())) {
            return '';
        }

        $deliveryCity = trim(sprintf('%s %s', (string) $order->getShippingPostalCode(), (string) $order->getShippingCity()));

        return sprintf(
            '<dt>Adresse de livraison</dt><dd>%s%s%s</dd>',
            $order->getShippingName() ? $this->formatter->escape($order->getShippingName()).'<br>' : '',
            $this->formatter->escape($order->getShippingAddress()),
            '' !== $deliveryCity ? '<br>'.$this->formatter->escape($deliveryCity) : '',
        );
    }
}
