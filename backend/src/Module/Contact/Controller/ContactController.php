<?php

declare(strict_types=1);

namespace App\Module\Contact\Controller;

use App\Shared\Http\ApiResponse;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/public/contact', name: 'api_public_contact', methods: ['POST'])]
class ContactController extends AbstractController
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly ValidatorInterface $validator,
        private readonly LoggerInterface $logger,
        #[Autowire(service: 'limiter.contact_public')]
        private readonly RateLimiterFactory $contactLimiter,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            return ApiResponse::error('Invalid JSON payload.', JsonResponse::HTTP_BAD_REQUEST);
        }

        // Rate limit by IP to limiter abuse
        $limiter = $this->contactLimiter->create($request->getClientIp() ?? 'anon');
        $limit = $limiter->consume(1);
        if (!$limit->isAccepted()) {
            return ApiResponse::error(
                'Trop de demandes. Merci de réessayer dans quelques instants.',
                JsonResponse::HTTP_TOO_MANY_REQUESTS
            );
        }

        // Trim incoming strings to avoid blank/space-only payloads
        $payload = array_map(
            static fn ($value) => is_string($value) ? trim($value) : $value,
            $payload
        );

        $constraints = new Assert\Collection([
            'name' => [new Assert\NotBlank(normalizer: 'trim'), new Assert\Length(max: 100)],
            'email' => [new Assert\NotBlank(normalizer: 'trim'), new Assert\Email(), new Assert\Length(max: 180)],
            'subject' => [new Assert\NotBlank(normalizer: 'trim'), new Assert\Length(max: 150)],
            'message' => [new Assert\NotBlank(normalizer: 'trim'), new Assert\Length(max: 5000)],
        ]);

        $violations = $this->validator->validate($payload, $constraints);
        if ($violations->count() > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[] = sprintf('%s: %s', $violation->getPropertyPath(), $violation->getMessage());
            }

            return ApiResponse::error('Validation failed.', JsonResponse::HTTP_UNPROCESSABLE_ENTITY, $errors);
        }

        $from = $_ENV['MAILER_FROM'] ?? 'no-reply@localhost';
        $to = $_ENV['CONTACT_RECIPIENT'] ?? $from;

        $email = (new Email())
            ->from(new Address($from, 'Hociatec'))
            ->to(new Address($to, 'Hociatec Contact'))
            ->replyTo(new Address($payload['email'], $payload['name']))
            ->subject('[Contact] ' . $payload['subject'])
            ->html(
                '<p><strong>Nom:</strong> ' . htmlspecialchars($payload['name']) . '</p>' .
                '<p><strong>Email:</strong> ' . htmlspecialchars($payload['email']) . '</p>' .
                '<p><strong>Message:</strong></p>' .
                '<p>' . nl2br(htmlspecialchars($payload['message'])) . '</p>'
            );

        try {
            $this->mailer->send($email);
        } catch (\Throwable $e) {
            $this->logger->error('Contact email send failed', ['exception' => $e]);

            return ApiResponse::error(
                "Impossible d'envoyer le message pour le moment.",
                JsonResponse::HTTP_SERVICE_UNAVAILABLE
            );
        }

        // Send an acknowledgement to the sender (non-blocking)
        try {
            $ack = (new Email())
                ->from(new Address($from, 'Hociatec'))
                ->to(new Address($payload['email'], $payload['name']))
                ->subject('Merci de nous avoir contactés')
                ->html(
                    '<p>Bonjour ' . htmlspecialchars($payload['name']) . ',</p>' .
                    '<p>Merci de nous avoir contactés. Nous avons bien reçu votre demande et allons la traiter rapidement.</p>' .
                    '<p>Résumé de votre message:</p>' .
                    '<blockquote style="border-left:4px solid #ddd;padding-left:8px;color:#444">' .
                    nl2br(htmlspecialchars($payload['message'])) .
                    '</blockquote>' .
                    '<p>Cet email est automatique, merci de ne pas y répondre. Nous reviendrons vers vous dès que possible.</p>'
                );
            $this->mailer->send($ack);
        } catch (\Throwable $e) {
            $this->logger->warning('Contact acknowledgement send failed', ['exception' => $e]);
        }

        return ApiResponse::success(['message' => 'Votre message a été envoyé.']);
    }
}
