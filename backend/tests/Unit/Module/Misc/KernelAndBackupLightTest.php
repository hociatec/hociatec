<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Misc;

use App\Kernel;
use App\Module\Admin\Application\Backup\Workflow\MaintenanceModeService;
use App\Module\Admin\UI\Backup\Controller\SystemStatusController;
use App\Module\System\Application\Provider\PrometheusMetricContractProvider;
use App\Module\System\UI\Controller\MetricsController;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DbalException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class KernelAndBackupLightTest extends TestCase
{
    public function testKernelBootAndSystemStatusController(): void
    {
        $kernel = new Kernel('test', true);
        $kernel->boot();
        self::assertTrue($kernel->getContainer()->has('service_container'));
        $kernel->shutdown();

        $directory = sys_get_temp_dir().'/hociatec-maintenance-'.bin2hex(random_bytes(4));
        mkdir($directory, 0777, true);
        $service = new MaintenanceModeService($directory);

        $payload = json_decode((string) (new SystemStatusController($service))()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertFalse($payload['data']['maintenance']['enabled']);

        @rmdir($directory);
    }

    public function testMetricsControllerRequiresLocalhostOrToken(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects(self::never())->method('executeQuery');

        $denied = (new MetricsController($connection, 'secret', new PrometheusMetricContractProvider()))(
            Request::create('/metrics', 'GET', server: ['REMOTE_ADDR' => '203.0.113.10'])
        );

        self::assertSame(Response::HTTP_FORBIDDEN, $denied->getStatusCode());
    }

    public function testMetricsControllerAcceptsConfiguredToken(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->method('executeQuery')->willThrowException(new DbalException('db down'));

        $request = Request::create('/metrics', 'GET', server: ['REMOTE_ADDR' => '203.0.113.10']);
        $request->headers->set('X-Metrics-Token', 'secret');
        $response = (new MetricsController($connection, 'secret', new PrometheusMetricContractProvider()))($request);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertStringContainsString('hociatec_database_up 0', (string) $response->getContent());
    }
}
