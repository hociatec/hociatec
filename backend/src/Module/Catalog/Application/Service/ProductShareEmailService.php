<?php

declare(strict_types=1);

namespace App\Module\Catalog\Application\Service;

use App\Infrastructure\Mail\MailDeliveryException;
use App\Module\Catalog\Domain\Entity\Product;
use App\Module\Marketing\Application\Service\EmailTemplateRenderer;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

final readonly class ProductShareEmailService
{
    public function __construct(
        private EmailTemplateRenderer $templates,
        private MailerInterface $mailer,
        private string $frontendUrl,
        private string $mailerFrom,
    ) {
    }

    public function send(Product $product, string $recipient): void
    {
        try {
            $this->deliver($product, $recipient);
        } catch (MailDeliveryException $exception) {
            throw $exception;
        } catch (\RuntimeException $exception) {
            throw MailDeliveryException::failed('product_share', $exception);
        }
    }

    private function deliver(Product $product, string $recipient): void
    {
        $frontendUrl = rtrim($this->frontendUrl, '/');
        $content = $this->templates->renderScenario('product_share', [
            'product_name' => $product->getName(),
            'product_summary' => $product->getShortDescription() ?: 'Consultez la fiche produit pour obtenir tous les détails.',
            'product_price_eur' => number_format($product->getEffectivePriceCents() / 100, 2, ',', ' '),
            'product_url' => $frontendUrl.'/catalogue/produits/'.rawurlencode($product->getSlug()),
            'app_frontend_url' => $frontendUrl,
        ], [
            'subject' => 'Découvrir : {{product_name}}',
            'html' => '<p>Bonjour,</p><p>Voici un produit qui pourrait vous intéresser :</p><p><strong>{{product_name}}</strong></p><p>{{product_summary}}</p><p><strong>Prix :</strong> {{product_price_eur}} EUR</p><p><a href="{{product_url}}">Voir la fiche produit</a></p>',
            'text' => "Bonjour,\n\nVoici un produit qui pourrait vous intéresser :\n\n{{product_name}}\n{{product_summary}}\nPrix : {{product_price_eur}} EUR\nVoir la fiche produit : {{product_url}}",
        ]);

        $email = (new Email())
            ->from(new Address($this->mailerFrom, 'Hociatec'))
            ->to(new Address($recipient))
            ->subject($content['subject'])
            ->html($content['html'])
            ->text($content['text']);

        try {
            $this->mailer->send($email);
        } catch (\RuntimeException $exception) {
            throw MailDeliveryException::failed('product_share', $exception);
        }
    }
}
