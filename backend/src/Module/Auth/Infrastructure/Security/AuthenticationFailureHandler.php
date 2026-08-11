<?php

declare(strict_types=1);

namespace App\Module\Auth\Infrastructure\Security;

use App\Shared\Infrastructure\Http\ApiResponse;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationFailureEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class AuthenticationFailureHandler implements AuthenticationFailureHandlerInterface
{
    public function __construct(
        private readonly EventDispatcherInterface $dispatcher,
    ) {
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        $message = $this->translateMessage(
            $exception->getMessageKey(),
            $exception->getMessageData(),
        );

        $response = ApiResponse::error(
            $message,
            self::mapExceptionCodeToStatusCode($exception->getCode()),
        );

        $event = new AuthenticationFailureEvent($exception, $response, $request);
        $this->dispatcher->dispatch($event, Events::AUTHENTICATION_FAILURE);

        return $event->getResponse() ?? $response;
    }

    /**
     * @param array<string, scalar|null> $data
     */
    private function translateMessage(string $messageKey, array $data): string
    {
        return match ($messageKey) {
            'Bad credentials.',
            'Invalid credentials.',
            'Authentication credentials could not be found.',
            'Account is disabled.',
            'Account is locked.',
            'Account has expired.',
            'Credentials have expired.' => 'Identifiants invalides.',
            'Too many failed login attempts, please try again later.' => 'Trop de tentatives de connexion échouées, veuillez réessayer plus tard.',
            'Too many failed login attempts, please try again in %minutes% minute.' => sprintf(
                'Trop de tentatives de connexion échouées, veuillez réessayer dans %s minute.',
                $data['%minutes%'] ?? '1',
            ),
            'Too many failed login attempts, please try again in %minutes% minutes.' => sprintf(
                'Trop de tentatives de connexion échouées, veuillez réessayer dans %s minutes.',
                $data['%minutes%'] ?? 'quelques',
            ),
            default => strtr($messageKey, array_map(
                static fn (mixed $value): string => is_scalar($value) ? (string) $value : '',
                $data,
            )),
        };
    }

    private static function mapExceptionCodeToStatusCode(string|int $exceptionCode): int
    {
        return is_int($exceptionCode) && $exceptionCode >= 400 && $exceptionCode < 500
            ? $exceptionCode
            : Response::HTTP_UNAUTHORIZED;
    }
}
