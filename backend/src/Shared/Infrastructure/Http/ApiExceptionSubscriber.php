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
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

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
        $response = $this->mapToResponse($exception);
        $status = $response->getStatusCode();

        if ($status >= JsonResponse::HTTP_INTERNAL_SERVER_ERROR) {
            $this->logger->error('Unhandled API exception.', [
                'exception' => $exception,
                'request_id' => $request->attributes->get(RequestIdSubscriber::ATTRIBUTE),
                'method' => $request->getMethod(),
                'path' => $request->getPathInfo(),
            ]);
        }

        $event->setResponse($response);
    }

    private function mapToResponse(\Throwable $exception): JsonResponse
    {
        if (
            $exception instanceof \JsonException
            || $exception instanceof HttpFoundationJsonException
            || $exception->getPrevious() instanceof \JsonException
            || $exception->getPrevious() instanceof HttpFoundationJsonException
        ) {
            return ApiResponse::badRequest('Payload JSON invalide.', code: 'INVALID_JSON_PAYLOAD');
        }

        if ($exception instanceof AccessDeniedException) {
            return ApiResponse::forbidden('Accès refusé.');
        }

        if ($exception instanceof AuthenticationException) {
            return ApiResponse::unauthorized('Authentification requise.');
        }

        if ($exception instanceof UniqueConstraintViolationException) {
            return ApiResponse::conflict('Une ressource avec ces informations existe déjà.', code: 'RESOURCE_ALREADY_EXISTS');
        }

        if ($exception instanceof ApiProblemException) {
            [$message, $status, $details, $code] = match (true) {
                $exception instanceof PublicApiException => [$exception->publicMessage(), $exception->getStatusCode(), $exception->details(), $exception->errorCode()],
                $exception instanceof ApiProblemException => [$exception->getStatusCode() >= JsonResponse::HTTP_INTERNAL_SERVER_ERROR ? 'Une erreur interne est survenue.' : 'Requête impossible.', $exception->getStatusCode(), $exception->details(), $exception->errorCode()],
            };

            return ApiResponse::error($message, $status, $details, $code);
        }

        return ApiProblemResponse::fromThrowable($exception);
    }
}
