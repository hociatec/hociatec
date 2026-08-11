<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/translate', name: 'api_translate', methods: ['POST'])]
final class TranslateController extends AbstractController
{
    private const ALLOWED_LANGUAGES = ['fr', 'en'];

    public function __construct(private readonly LibreTranslateClient $translator)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $payload = JsonRequestInput::payload($request);
        $text = isset($payload['q']) ? trim((string) $payload['q']) : '';
        $source = isset($payload['source']) ? strtolower(trim((string) $payload['source'])) : '';
        $target = isset($payload['target']) ? strtolower(trim((string) $payload['target'])) : '';

        if ('' === $text || '' === $source || '' === $target) {
            return ApiResponse::error('Paramètres de traduction manquants.', Response::HTTP_BAD_REQUEST);
        }

        if (!in_array($source, self::ALLOWED_LANGUAGES, true) || !in_array($target, self::ALLOWED_LANGUAGES, true)) {
            return ApiResponse::error('Langue non supportée.', Response::HTTP_BAD_REQUEST);
        }

        try {
            $translated = $this->translator->translate($text, $source, $target);
        } catch (\InvalidArgumentException) {
            $translated = $text;
        }

        return new JsonResponse(['translatedText' => $translated]);
    }
}
