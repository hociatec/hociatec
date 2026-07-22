<?php

declare(strict_types=1);

namespace App\Module\Catalog\Controller\PublicApi;

use App\Module\Catalog\Service\ProductService;
use App\Module\Marketing\Service\EmailTemplateRenderer;
use App\Shared\Http\ApiResponse;
use App\Shared\Http\OvhRoundcubeMailer;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\RateLimiter\Annotation\RateLimiter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/public/catalog/products/{slug}/share', name: 'api_public_catalog_products_share', methods: ['POST'])]
#[RateLimiter('product_share_public')]
class ShareProductEmailController extends AbstractController
{
    public function __construct(
        private readonly ProductService $productService,
        private readonly MailerInterface $mailer,
        private readonly OvhRoundcubeMailer $ovhRoundcubeMailer,
        private readonly ValidatorInterface $validator,
        private readonly LoggerInterface $logger,
        private readonly EmailTemplateRenderer $emailTemplates,
    ) {
    }

    public function __invoke(Request $request, string $slug): JsonResponse
    {
        $product = $this->productService->findPublishedBySlug($slug);
        if ($product === null) {
            return ApiResponse::error('Produit introuvable.', JsonResponse::HTTP_NOT_FOUND);
        }

        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            return ApiResponse::error('Payload JSON invalide.', JsonResponse::HTTP_BAD_REQUEST);
        }

        $payload = array_map(
            static fn ($value) => is_string($value) ? trim($value) : $value,
            $payload
        );

        $violations = $this->validator->validate(
            $payload,
            new Assert\Collection([
                'email' => [
                    new Assert\NotBlank(normalizer: 'trim'),
                    new Assert\Email(),
                    new Assert\Length(max: 180),
                ],
            ])
        );

        if ($violations->count() > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[] = sprintf('%s: %s', $violation->getPropertyPath(), $violation->getMessage());
            }

            return ApiResponse::error('Validation des donnees echouee.', JsonResponse::HTTP_UNPROCESSABLE_ENTITY, $errors);
        }

        $recipientEmail = (string) ($payload['email'] ?? '');
        $from = $_ENV['MAILER_FROM'] ?? 'no-reply@localhost';
        $frontendUrl = rtrim((string) ($_ENV['APP_FRONTEND_URL'] ?? 'http://localhost:5173'), '/');
        $productUrl = $frontendUrl . '/catalogue/produits/' . rawurlencode($product->getSlug());
        $summary = $product->getShortDescription() ?: 'Consultez la fiche produit pour obtenir tous les détails.';
        $content = $this->emailTemplates->renderScenario('product_share', [
            'product_name' => $product->getName(),
            'product_summary' => $summary,
            'product_price_eur' => number_format($product->getEffectivePriceCents() / 100, 2, ',', ' '),
            'product_url' => $productUrl,
            'app_frontend_url' => $frontendUrl,
        ], [
            'subject' => 'Découvrir : {{product_name}}',
            'html' => '<p>Bonjour,</p><p>Voici un produit qui pourrait vous intéresser :</p><p><strong>{{product_name}}</strong></p><p>{{product_summary}}</p><p><strong>Prix :</strong> {{product_price_eur}} EUR</p><p><a href="{{product_url}}">Voir la fiche produit</a></p>',
            'text' => "Bonjour,\n\nVoici un produit qui pourrait vous intéresser :\n\n{{product_name}}\n{{product_summary}}\nPrix : {{product_price_eur}} EUR\nVoir la fiche produit : {{product_url}}",
        ]);

        try {
            $this->ovhRoundcubeMailer->send($recipientEmail, $content['subject'], $content['text']);
        } catch (\Throwable $exception) {
            $this->logger->warning('Product share email send failed with OVH Roundcube primary transport', [
                'exception' => $exception,
                'productId' => $product->getId(),
                'recipient' => $recipientEmail,
            ]);

            try {
                $email = (new Email())
                    ->from(new Address($from, 'Hociatec'))
                    ->to(new Address($recipientEmail))
                    ->subject($content['subject'])
                    ->html($content['html'])
                    ->text($content['text']);

                $this->mailer->send($email);
            } catch (\Throwable $fallbackException) {
                $this->logger->error('Product share email send failed with SMTP fallback', [
                    'exception' => $fallbackException,
                    'productId' => $product->getId(),
                    'recipient' => $recipientEmail,
                ]);

                return ApiResponse::error(
                    "Impossible d'envoyer le message pour le moment.",
                    JsonResponse::HTTP_SERVICE_UNAVAILABLE
                );
            }
        }

        return ApiResponse::success([
            'sent' => true,
            'to' => $recipientEmail,
            'message' => 'Le produit a été envoyé par e-mail.',
        ]);
    }
}
