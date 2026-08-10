<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Admin\BetaTest;

use App\Module\Admin\UI\BetaTest\Controller\ExportBetaTestersController;
use App\Module\BetaTest\Application\Port\BetaTesterProfileRepositoryPort;
use App\Module\BetaTest\Domain\Entity\BetaTesterProfile;
use App\Module\User\Domain\Entity\User;
use App\Shared\Application\LockMode;
use App\Shared\Infrastructure\Http\AttachmentResponseFactory;
use PHPUnit\Framework\TestCase;

final class ExportBetaTestersControllerTest extends TestCase
{
    public function testExportBetaTestersStreamsCsvRows(): void
    {
        $profiles = [
            (new BetaTesterProfile(
                $this->user('ada@example.test', 'Ada', 'Lovelace'),
                ['weekdays', 'evenings'],
                'Motivation',
                'manual',
                'clear',
                'advanced',
                'screen-reader',
                ['nvda'],
                ['windows'],
                ['firefox'],
                ['ui', 'api'],
                new \DateTimeImmutable('2026-08-01T10:00:00+00:00'),
                '2026-07'
            ))->setStatus(BetaTesterProfile::STATUS_ACCEPTED),
        ];

        $controller = new ExportBetaTestersController(
            new class($profiles) implements BetaTesterProfileRepositoryPort {
                /** @param list<BetaTesterProfile> $profiles */
                public function __construct(private readonly array $profiles)
                {
                }

                public function find(mixed $id, LockMode|int|null $lockMode = null, ?int $lockVersion = null): ?BetaTesterProfile
                {
                    return null;
                }

                public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array
                {
                    return 0 === $offset ? $this->profiles : [];
                }

                public function count(array $criteria): int
                {
                    return count($this->profiles);
                }

                public function findOneByUser(User $user): ?BetaTesterProfile
                {
                    return null;
                }

                public function findForAdminList(string $search = '', string $status = '', string $accessibility = '', int $limit = 20, int $offset = 0): array
                {
                    return [];
                }

                public function countForAdminList(string $search = '', string $status = '', string $accessibility = ''): int
                {
                    return 0;
                }
            },
            new AttachmentResponseFactory(),
        );

        $response = $controller();

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('beta-testeurs.csv', (string) $response->headers->get('Content-Disposition'));

        ob_start();
        $response->sendContent();
        $csv = (string) ob_get_clean();

        self::assertStringContainsString("Prénom;Nom;E-mail;État;", $csv);
        self::assertStringContainsString('Ada;Lovelace;ada@example.test;accepted;', $csv);
        self::assertStringContainsString('weekdays, evenings', $csv);
        self::assertStringContainsString('ui, api', $csv);
    }

    private function user(string $email, string $firstName, string $lastName): User
    {
        return new User($email, $firstName, $lastName, new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
    }
}
