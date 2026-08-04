<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\Backup\Controller;

use App\Infrastructure\Http\ApiResponse;
use App\Module\Admin\Application\Backup\Service\MaintenanceModeService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class MaintenanceModeSubscriber implements EventSubscriberInterface
{
    /**
     * @var list<string>
     */
    private const ALLOWED_PREFIXES = [
        '/api/public/system/status',
        '/api/csrf-token',
        '/api/auth/login',
        '/api/auth/logout',
        '/api/auth/profile',
        '/api/admin',
    ];

    public function __construct(private readonly MaintenanceModeService $maintenanceModeService)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 16],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest() || !$this->maintenanceModeService->isEnabled()) {
            return;
        }

        $path = $event->getRequest()->getPathInfo();
        if (!str_starts_with($path, '/api/')) {
            return;
        }

        foreach (self::ALLOWED_PREFIXES as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix.'/')) {
                return;
            }
        }

        $status = $this->maintenanceModeService->getStatus();
        $event->setResponse(ApiResponse::error($status['message'], Response::HTTP_SERVICE_UNAVAILABLE));
    }
}
