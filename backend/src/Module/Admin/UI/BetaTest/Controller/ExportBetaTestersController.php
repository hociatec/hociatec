<?php

declare(strict_types=1);

namespace App\Module\Admin\UI\BetaTest\Controller;

use App\Module\BetaTest\Application\Port\BetaTesterProfileRepositoryPort;
use App\Shared\Infrastructure\Http\AttachmentResponseFactory;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/beta-testers/export', methods: ['GET'])] #[IsGranted('ROLE_BETA_MANAGER')]
final class ExportBetaTestersController
{
    private const BATCH_SIZE = 200;

    public function __construct(
        private readonly BetaTesterProfileRepositoryPort $profiles,
        private readonly AttachmentResponseFactory $attachments,
    ) {
    }

    public function __invoke(): StreamedResponse
    {
        $response = new StreamedResponse(function (): void {
            $output = fopen('php://output', 'wb');
            if (false === $output) {
                throw new \RuntimeException('Impossible d\'ouvrir le flux d\'export.');
            }

            fputcsv($output, ['Prénom', 'Nom', 'E-mail', 'État', 'Accessibilité', 'Disponibilités', 'Appareils', 'Navigateurs', 'Types de tests', 'Créé le'], ';');
            $offset = 0;
            do {
                $profiles = $this->profiles->findBy([], ['createdAt' => 'DESC'], self::BATCH_SIZE, $offset);
                foreach ($profiles as $p) {
                    $u = $p->getUser();
                    fputcsv($output, [$u->getFirstName(), $u->getLastName(), $u->getEmail(), $p->getStatus(), $p->getAccessibilityNeed(), implode(', ', $p->getAvailability()), implode(', ', $p->getDevices()), implode(', ', $p->getBrowsers()), implode(', ', $p->getTestingTypes()), $p->getCreatedAt()->format(DATE_ATOM)], ';');
                }
                $offset += self::BATCH_SIZE;
            } while (self::BATCH_SIZE === count($profiles));

            fclose($output);
        });
        $this->attachments->applyHeaders($response, 'beta-testeurs.csv', 'text/csv; charset=UTF-8');

        return $response;
    }
}
