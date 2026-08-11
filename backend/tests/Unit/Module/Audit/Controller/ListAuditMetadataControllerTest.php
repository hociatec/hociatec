<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Audit\Controller;

use App\Module\Audit\Application\Projection\AuditMetadataFormatter;
use App\Module\Audit\UI\Controller\Client\ListAuditMetadataController;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

final class ListAuditMetadataControllerTest extends TestCase
{
    public function testReturnsTypesAndStatuses(): void
    {
        $response = (new ListAuditMetadataController(new AuditMetadataFormatter()))();
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame('success', $payload['status']);
        self::assertNotEmpty($payload['data']['types']);
        self::assertNotEmpty($payload['data']['statuses']);
    }
}
