<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http;

use App\Shared\Application\Exception\ApiProblemException;
use App\Shared\Application\Exception\PublicApiException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Exception\JsonException as HttpFoundationJsonException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

#[AsEventListener(event: 'kernel.exception', priority: 20)]
final readonly class ApiExceptionSubscriber
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    public function __invoke(ExceptionEvent $event): void
    {
        $request = $event->getRequest();
        if (!str_starts_with($request->getPathInfo(), '/api/')) {
            return;
        }

        $exception = $event->getThrowable();
        [$message, $status, $details] = $this->map($exception);

        if ($status >= JsonResponse::HTTP_INTERNAL_SERVER_ERROR) {
            $this->logger->error('Unhandled API exception.', [
                'exception' => $exception,
                'request_id' => $request->attributes->get(RequestIdSubscriber::ATTRIBUTE),
                'method' => $request->getMethod(),
                'path' => $request->getPathInfo(),
            ]);
        }

        $event->setResponse(ApiResponse::error($message, $status, $details));
    }

    /**
     * @return array{0: string, 1: int, 2: list<string>}
     */
    private function map(\Throwable $exception): array
    {
        if ($exception instanceof ApiValidationException) {
            return [$exception->getMessage(), $exception->statusCode, $exception->details];
        }

        if (
            $exception instanceof \JsonException
            || $exception instanceof HttpFoundationJsonException
            || $exception->getPrevious() instanceof \JsonException
            || $exception->getPrevious() instanceof HttpFoundationJsonException
        ) {
            return ['Payload JSON invalide.', JsonResponse::HTTP_BAD_REQUEST, []];
        }

        if ($exception instanceof HttpExceptionInterface) {
            $message = $exception->getStatusCode() >= 500
                ? 'Une erreur interne est survenue.'
                : ($exception->getMessage() ?: 'Requête impossible.');

            return [$message, $exception->getStatusCode(), []];
        }

        return match (true) {
            $exception instanceof AccessDeniedException => ['Accès refusé.', JsonResponse::HTTP_FORBIDDEN, []],
            $exception instanceof UniqueConstraintViolationException => ['Une ressource avec ces informations existe déjà.', JsonResponse::HTTP_CONFLICT, []],
            $exception instanceof PublicApiException => [$exception->publicMessage(), $exception->getStatusCode(), []],
            $exception instanceof ApiProblemException => [$exception->getMessage(), $exception->getStatusCode(), []],
            $exception instanceof \DomainException => ['Requête impossible.', JsonResponse::HTTP_UNPROCESSABLE_ENTITY, []],
            $exception instanceof \InvalidArgumentException => ['Requête invalide.', JsonResponse::HTTP_BAD_REQUEST, []],
            default => ['Une erreur interne est survenue.', JsonResponse::HTTP_INTERNAL_SERVER_ERROR, []],
        };
    }
}
