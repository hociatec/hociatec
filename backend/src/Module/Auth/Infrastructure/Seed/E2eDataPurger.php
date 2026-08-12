<?php

declare(strict_types=1);

namespace App\Module\Auth\Infrastructure\Seed;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;

class E2eDataPurger
{
    private const STABLE_E2E_EMAILS = [
        'e2e.client@hociatec.local',
        'e2e.admin@hociatec.local',
    ];

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function purge(): int
    {
        $connection = $this->entityManager->getConnection();

        return (int) $connection->transactional(function (Connection $connection): int {
            $deletedRows = 0;

            $deletedRows += $this->deleteTradeInRequests($connection);
            $deletedRows += $this->deleteBugReports($connection);
            $deletedRows += $this->deleteNewsComments($connection);
            $deletedRows += $this->deleteShippingAddresses($connection);
            $deletedRows += $this->deleteVouchers($connection);
            $deletedRows += $this->deleteTrainings($connection);
            $deletedRows += $this->deleteBrands($connection);
            $deletedRows += $this->deletePrestations($connection);
            $deletedRows += $this->deleteNewsArticles($connection);
            $deletedRows += $this->deletePromotions($connection);
            $deletedRows += $this->deleteServices($connection);
            $deletedRows += $this->deleteOrders($connection);
            $deletedRows += $this->deleteUsers($connection);

            return $deletedRows;
        });
    }

    private function deleteTradeInRequests(Connection $connection): int
    {
        return $this->deleteLikeAny(
            $connection,
            'trade_in_requests',
            [
                'email' => ['e2e.tradein.%@example.test', 'e2e.tradein.admin.%@example.test'],
                'product_name' => ['MacBook Pro E2E %', 'MacBook Pro Admin E2E %'],
            ],
        );
    }

    private function deleteBugReports(Connection $connection): int
    {
        return $this->deleteLikeAny($connection, 'beta_bug_reports', [
            'title' => ['Signalement beta E2E %', 'Signalement bêta E2E %'],
        ]);
    }

    private function deleteNewsComments(Connection $connection): int
    {
        return $this->deleteLikeAny($connection, 'news_comments', [
            'content' => ['Commentaire actualite E2E %', 'Commentaire actualité E2E %'],
        ]);
    }

    private function deleteShippingAddresses(Connection $connection): int
    {
        return $this->deleteLikeAny($connection, 'user_shipping_addresses', [
            'name' => ['Adresse E2E %'],
        ]);
    }

    private function deleteVouchers(Connection $connection): int
    {
        return $this->deleteLikeAny($connection, 'vouchers', [
            'name' => ['Bon E2E %', 'Bon client E2E %'],
            'description' => ['Bon E2E %'],
        ]);
    }

    private function deleteTrainings(Connection $connection): int
    {
        return $this->deleteLikeAny($connection, 'trainings', [
            'title' => ['Formation E2E %'],
        ]);
    }

    private function deleteBrands(Connection $connection): int
    {
        return $this->deleteLikeAny($connection, 'catalog_brands', [
            'name' => ['Marque E2E %'],
        ]);
    }

    private function deletePrestations(Connection $connection): int
    {
        return $this->deleteLikeAny($connection, 'appointment_prestations', [
            'name' => ['Motif E2E %', 'Diagnostic e2e prioritaire'],
        ]);
    }

    private function deleteNewsArticles(Connection $connection): int
    {
        return $this->deleteLikeAny($connection, 'news_articles', [
            'title' => ['Actualite E2E %', 'Actualité E2E %'],
        ]);
    }

    private function deletePromotions(Connection $connection): int
    {
        return $this->deleteLikeAny($connection, 'promotions', [
            'name' => ['Promotion E2E %'],
        ]);
    }

    private function deleteServices(Connection $connection): int
    {
        return $this->deleteLikeAny($connection, 'quote_services', [
            'title' => ['Service E2E %'],
        ]);
    }

    private function deleteOrders(Connection $connection): int
    {
        return $this->deleteLikeAny($connection, 'orders', [
            'number' => ['ORD-E2E-%', 'ORD-PLAY-ADMIN-%'],
        ]);
    }

    private function deleteUsers(Connection $connection): int
    {
        return $connection->executeStatement(
            'DELETE FROM users WHERE LOWER(email) IN (:emails)',
            ['emails' => array_map(static fn (string $email): string => strtolower($email), self::STABLE_E2E_EMAILS)],
            ['emails' => Connection::PARAM_STR_ARRAY],
        );
    }

    /**
     * @param array<string, list<string>> $patternsByColumn
     */
    private function deleteLikeAny(Connection $connection, string $table, array $patternsByColumn): int
    {
        $conditions = [];
        $parameters = [];
        $index = 0;

        foreach ($patternsByColumn as $column => $patterns) {
            foreach ($patterns as $pattern) {
                $parameter = 'pattern_'.$index;
                $conditions[] = sprintf('%s LIKE :%s', $column, $parameter);
                $parameters[$parameter] = $pattern;
                ++$index;
            }
        }

        if ([] === $conditions) {
            return 0;
        }

        return $connection->executeStatement(
            sprintf('DELETE FROM %s WHERE %s', $table, implode(' OR ', $conditions)),
            $parameters,
        );
    }
}
