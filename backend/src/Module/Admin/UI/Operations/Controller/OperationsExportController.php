<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\Operations\Controller;

use App\Module\Admin\Application\Operations\Exporter\AdminOperationsExporter;
use App\Shared\Infrastructure\Http\AttachmentResponseFactory;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/operations/exports/{resource}.csv', name: 'api_admin_operations_exports', methods: ['GET'])]
#[IsGranted('ROLE_OPERATIONS')]
final readonly class OperationsExportController
{
    public function __construct(
        private AdminOperationsExporter $exporter,
        private AttachmentResponseFactory $attachments,
    ) {
    }

    public function __invoke(string $resource): StreamedResponse
    {
        $rows = $this->exporter->rowsFor($resource);
        $response = new StreamedResponse(static function () use ($rows): void {
            $output = fopen('php://output', 'wb');
            if (false === $output) {
                throw new \RuntimeException('Impossible d’ouvrir le flux d’export.');
            }

            foreach ($rows as $row) {
                fputcsv($output, $row, ';');
            }
            fclose($output);
        });
        $this->attachments->applyHeaders($response, $resource.'.csv', 'text/csv; charset=UTF-8');

        return $response;
    }
}
