<?php

declare(strict_types=1);

namespace App\Module\Catalog\Controller\PublicApi;

use App\Module\Catalog\Service\ProductService;
use App\Shared\Http\ApiResponse;
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
        private readonly ValidatorInterface $validator,
        private readonly LoggerInterface $logger,
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
            return ApiResponse::error('Invalid JSON payload.', JsonResponse::HTTP_BAD_REQUEST);
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

            return ApiResponse::error('Validation failed.', JsonResponse::HTTP_UNPROCESSABLE_ENTITY, $errors);
        }

        $recipientEmail = (string) ($payload['email'] ?? '');
        $from = $_ENV['MAILER_FROM'] ?? 'no-reply@localhost';
        $productUrl = rtrim($request->getSchemeAndHttpHost(), '/') . '/catalogue/produits/' . rawurlencode($product->getSlug());
        $subject = sprintf('Decouvrir : %s', $product->getName());
        $summary = $product->getShortDescription() ?: 'Consultez la fiche produit pour obtenir tous les details.';

        $email = (new Email())
            ->from(new Address($from, 'Hociatec'))
            ->to(new Address($recipientEmail))
            ->subject($subject)
            ->html(
                '<p>Bonjour,</p>' .
                '<p>Voici un produit qui pourrait vous interesser :</p>' .
                '<p><strong>' . htmlspecialchars($product->getName()) . '</strong></p>' .
                '<p>' . htmlspecialchars($summary) . '</p>' .
                '<p><strong>Prix :</strong> ' . number_format($product->getEffectivePriceCents() / 100, 2, ',', ' ') . ' EUR</p>' .
                '<p><a href="' . htmlspecialchars($productUrl) . '">Voir la fiche produit</a></p>'
            );

        try {
            $this->mailer->send($email);
        } catch (\Throwable $exception) {
            $this->logger->error('Product share email send failed', [
                'exception' => $exception,
                'productId' => $product->getId(),
                'recipient' => $recipientEmail,
            ]);

            return ApiResponse::error(
                "Impossible d'envoyer le message pour le moment.",
                JsonResponse::HTTP_SERVICE_UNAVAILABLE
            );
        }

        return ApiResponse::success([
            'sent' => true,
            'to' => $recipientEmail,
            'message' => 'Le produit a ete envoye par e-mail.',
        ]);
    }
}
