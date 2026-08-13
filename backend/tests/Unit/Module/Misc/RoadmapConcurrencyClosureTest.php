<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Misc;

use App\Module\Admin\Application\Operations\Persistence\OperationsPersistence;
use App\Module\Admin\Application\Operations\Projection\AdminOperationsEmailLogFormatter;
use App\Module\Admin\Application\Operations\Projection\AdminOperationsFormatter;
use App\Module\Admin\Application\Operations\Workflow\StockOperationsService;
use App\Module\Appointment\Application\Handler\ChangeAppointmentStatusHandler;
use App\Module\Appointment\Application\Port\AppointmentRepositoryPort;
use App\Module\Appointment\Application\Port\WorkingDayConfigurationRepositoryPort;
use App\Module\Appointment\Application\Workflow\AppointmentService;
use App\Module\Appointment\Application\Workflow\AppointmentStatusWorkflow;
use App\Module\Appointment\Application\Workflow\AvailabilityService;
use App\Module\Appointment\Domain\Entity\Appointment;
use App\Module\Appointment\Domain\Entity\Prestation;
use App\Module\Appointment\Domain\Entity\WorkingDayConfiguration;
use App\Module\Catalog\Application\Port\ProductCatalogRepository;
use App\Module\Catalog\Application\Port\StockMovementRepositoryPort;
use App\Module\Catalog\Domain\Entity\Category;
use App\Module\Catalog\Domain\Entity\Product;
use App\Module\Order\Application\Port\OrderEventRepositoryPort;
use App\Module\Order\Application\Port\OrderRepositoryPort;
use App\Module\Order\Application\Projection\OrderFormatter;
use App\Module\Order\Application\Projection\OrderItemFormatter;
use App\Module\Order\Application\Projection\OrderStatusLabelFormatter;
use App\Module\Order\Domain\Entity\Order;
use App\Module\Order\Domain\Entity\OrderEvent;
use App\Module\Order\Domain\Workflow\OrderStatusWorkflow;
use App\Module\Rating\Application\Projection\ProductReviewFormatter;
use App\Module\User\Domain\Entity\User;
use App\Module\Voucher\Application\Handler\CreateVoucherHandler;
use App\Module\Voucher\Application\Mapper\VoucherPayload;
use App\Module\Voucher\Application\Port\VoucherRepositoryPort;
use App\Module\Voucher\Domain\Entity\Voucher;
use App\Shared\Application\LockMode;
use App\Shared\Application\TransactionManager;
use App\Shared\Application\UnitOfWork;
use Doctrine\DBAL\Driver\Exception as DriverException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;

final class RoadmapConcurrencyClosureTest extends TestCase
{
    public function testConcurrentVoucherCreationAllowsOnlyOneWinnerForSameCode(): void
    {
        if (!extension_loaded('pcntl')) {
            self::markTestSkipped('pcntl extension is required.');
        }

        $workspace = sys_get_temp_dir().'/voucher-concurrency-'.bin2hex(random_bytes(6));
        self::assertTrue(mkdir($workspace, 0777, true));

        $statePath = $workspace.'/voucher-state.php';
        $lockPath = $workspace.'/voucher.lock';
        $signalPath = $workspace.'/voucher.signal';
        $childResultPath = $workspace.'/voucher-child.php';

        try {
            $payload = [
                'name' => 'Summer promo',
                'code' => 'SUMMER-25',
                'discountType' => Voucher::TYPE_PERCENT,
                'discountValue' => 25,
            ];

            $pid = pcntl_fork();
            self::assertNotSame(-1, $pid);

            if (0 === $pid) {
                try {
                    $handler = new CreateVoucherHandler(
                        new FileVoucherUnitOfWork($statePath, $lockPath, $signalPath, 700_000),
                        new VoucherPayload(new FileVoucherRepository($statePath)),
                    );
                    $voucher = $handler->create($payload);
                    file_put_contents($childResultPath, serialize(['success' => true, 'code' => $voucher->getCode()]), LOCK_EX);
                    exit(0);
                } catch (\Throwable $exception) {
                    file_put_contents($childResultPath, serialize($exception), LOCK_EX);
                    exit(1);
                }
            }

            $this->awaitSignal($signalPath);

            $handler = new CreateVoucherHandler(
                new FileVoucherUnitOfWork($statePath, $lockPath),
                new VoucherPayload(new FileVoucherRepository($statePath)),
            );

            $parentException = null;
            try {
                $voucher = $handler->create($payload);
                $parentResult = ['success' => true, 'code' => $voucher->getCode()];
            } catch (\Throwable $exception) {
                $parentException = $exception;
                $parentResult = ['success' => false, 'message' => $exception->getMessage()];
            }

            pcntl_waitpid($pid, $status);
            self::assertTrue(pcntl_wifexited($status));
            self::assertFileExists($childResultPath);

            $childResult = unserialize((string) file_get_contents($childResultPath), ['allowed_classes' => true]);
            $results = [$parentResult];
            if ($childResult instanceof \Throwable) {
                $results[] = ['success' => false, 'message' => $childResult->getMessage()];
            } else {
                $results[] = $childResult;
            }

            self::assertSame(1, count(array_filter($results, static fn (array $result): bool => true === $result['success'])));
            self::assertContains('SUMMER-25', array_column(array_filter($results, static fn (array $result): bool => true === $result['success']), 'code'));
            self::assertContains('Ce code existe déjà.', array_column(array_filter($results, static fn (array $result): bool => false === $result['success']), 'message'));
            self::assertSame(['SUMMER-25'], $this->loadVoucherCodes($statePath));
            self::assertTrue(null === $parentException || $parentException instanceof \InvalidArgumentException);
        } finally {
            $this->cleanupPaths([$childResultPath, $signalPath, $statePath, $lockPath], $workspace);
        }
    }

    public function testConcurrentAppointmentBookingAllowsOnlyOneWinnerForSameSlot(): void
    {
        if (!extension_loaded('pcntl')) {
            self::markTestSkipped('pcntl extension is required.');
        }

        $workspace = sys_get_temp_dir().'/appointment-concurrency-'.bin2hex(random_bytes(6));
        self::assertTrue(mkdir($workspace, 0777, true));

        $statePath = $workspace.'/appointment-state.php';
        $lockPath = $workspace.'/appointment.lock';
        $signalPath = $workspace.'/appointment.signal';
        $childResultPath = $workspace.'/appointment-child.php';

        try {
            $user = $this->user('appointment@example.test');
            $prestation = new Prestation('Diagnostic', 60, 9000);
            $startAt = new \DateTimeImmutable('2026-08-17T09:00:00+00:00');
            file_put_contents($statePath, serialize([]), LOCK_EX);

            $pid = pcntl_fork();
            self::assertNotSame(-1, $pid);

            if (0 === $pid) {
                try {
                    $service = $this->appointmentService($statePath, $lockPath, $signalPath, 700_000);
                    $appointment = $service->book($user, $prestation, $startAt);
                    file_put_contents($childResultPath, serialize(['success' => true, 'startAt' => $appointment->getStartAt()->format(DATE_ATOM)]), LOCK_EX);
                    exit(0);
                } catch (\Throwable $exception) {
                    file_put_contents($childResultPath, serialize($exception), LOCK_EX);
                    exit(1);
                }
            }

            $this->awaitSignal($signalPath);

            $service = $this->appointmentService($statePath, $lockPath);
            $parentFailure = null;
            try {
                $appointment = $service->book($user, $prestation, $startAt);
                $parentResult = ['success' => true, 'startAt' => $appointment->getStartAt()->format(DATE_ATOM)];
            } catch (\Throwable $exception) {
                $parentFailure = $exception;
                $parentResult = ['success' => false, 'message' => $exception->getMessage()];
            }

            pcntl_waitpid($pid, $status);
            self::assertTrue(pcntl_wifexited($status));
            self::assertFileExists($childResultPath);

            $childResult = unserialize((string) file_get_contents($childResultPath), ['allowed_classes' => true]);
            $results = [$parentResult];
            if ($childResult instanceof \Throwable) {
                $results[] = ['success' => false, 'message' => $childResult->getMessage()];
            } else {
                $results[] = $childResult;
            }

            self::assertSame(1, count(array_filter($results, static fn (array $result): bool => true === $result['success'])));
            self::assertContains('2026-08-17T09:00:00+00:00', array_column(array_filter($results, static fn (array $result): bool => true === $result['success']), 'startAt'));
            self::assertContains('Ce creneau n\'est plus disponible.', array_column(array_filter($results, static fn (array $result): bool => false === $result['success']), 'message'));
            self::assertCount(1, $this->loadAppointments($statePath));
            self::assertTrue(null === $parentFailure || $parentFailure instanceof \Throwable);
        } finally {
            $this->cleanupPaths([$childResultPath, $signalPath, $statePath, $lockPath], $workspace);
        }
    }

    public function testConcurrentStockMutationsRemainSerialized(): void
    {
        if (!extension_loaded('pcntl')) {
            self::markTestSkipped('pcntl extension is required.');
        }

        $workspace = sys_get_temp_dir().'/stock-concurrency-'.bin2hex(random_bytes(6));
        self::assertTrue(mkdir($workspace, 0777, true));

        $statePath = $workspace.'/stock-state.php';
        $lockPath = $workspace.'/stock.lock';
        $signalPath = $workspace.'/stock.signal';
        $childResultPath = $workspace.'/stock-child.php';

        try {
            $product = new Product('Phone', 'phone', 'PH-1', 'Phone', 100000, 5, new Category('Phones', 'phones'));
            $this->setEntityId($product, 55);
            $this->writeStockState($statePath, ['product' => serialize($product), 'movements' => []]);

            $pid = pcntl_fork();
            self::assertNotSame(-1, $pid);

            if (0 === $pid) {
                try {
                    $service = $this->stockService($statePath, $lockPath, $signalPath, 700_000);
                    $output = $service->create(55, -4, 'reservation', null, null);
                    file_put_contents($childResultPath, serialize($output->toArray()), LOCK_EX);
                    exit(0);
                } catch (\Throwable $exception) {
                    file_put_contents($childResultPath, serialize($exception), LOCK_EX);
                    exit(1);
                }
            }

            $this->awaitSignal($signalPath);

            $service = $this->stockService($statePath, $lockPath);
            $parentOutput = $service->create(55, -4, 'reservation', null, null)->toArray();

            pcntl_waitpid($pid, $status);
            self::assertTrue(pcntl_wifexited($status));
            self::assertFileExists($childResultPath);

            $childResult = unserialize((string) file_get_contents($childResultPath), ['allowed_classes' => true]);
            self::assertIsArray($childResult);
            self::assertSame([-4, -1], $this->sortedDeltas([$parentOutput, $childResult]));

            $state = $this->readStockState($statePath);
            $finalProduct = unserialize($state['product'], ['allowed_classes' => true]);
            self::assertInstanceOf(Product::class, $finalProduct);
            self::assertSame(0, $finalProduct->getStock());
            self::assertSame([
                ['delta' => -4, 'stockBefore' => 5, 'stockAfter' => 1],
                ['delta' => -1, 'stockBefore' => 1, 'stockAfter' => 0],
            ], $state['movements']);
        } finally {
            $this->cleanupPaths([$childResultPath, $signalPath, $statePath, $lockPath], $workspace);
        }
    }

    private function appointmentService(string $statePath, string $lockPath, ?string $signalPath = null, int $sleepMicrosAfterLock = 0): AppointmentService
    {
        $appointments = new FileAppointmentRepository($statePath);
        $workingDays = new FileWorkingDayConfigurationRepository($lockPath, $signalPath, $sleepMicrosAfterLock);
        $persistence = new FileAppointmentUnitOfWork($appointments);

        return new AppointmentService(
            $appointments,
            $workingDays,
            new AvailabilityService($workingDays, $appointments),
            new ChangeAppointmentStatusHandler(new AppointmentStatusWorkflow(), $persistence),
            $persistence,
            new FileLockTransactionManager($workingDays),
            new MockClock('2026-08-11T10:00:00+00:00'),
        );
    }

    private function stockService(string $statePath, string $lockPath, ?string $signalPath = null, int $sleepMicrosAfterLock = 0): StockOperationsService
    {
        $products = new FileProductCatalogRepository($statePath, $lockPath, $signalPath, $sleepMicrosAfterLock);
        $persistence = new FileStockPersistence($products, $statePath);

        return new StockOperationsService(
            $products,
            new class implements StockMovementRepositoryPort {
                public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array
                {
                    return [];
                }
            },
            $persistence,
            new FileLockTransactionManager($products),
            new AdminOperationsFormatter(
                new AdminOperationsEmailLogFormatter(
                    new class implements OrderRepositoryPort {
                        public function find(mixed $id, LockMode|int|null $lockMode = null, ?int $lockVersion = null): ?Order { return null; }
                        public function findForUpdate(int $id): ?Order { return null; }
                        public function countForYear(int $year): int { return 0; }
                        public function countInvoicedForYear(int $year): int { return 0; }
                        public function hasActiveForUser(User $user): bool { return false; }
                        public function findByUser(User $user, int $limit = 20, int $offset = 0): array { return []; }
                        public function countByUser(User $user): int { return 0; }
                        public function findForUserList(User $user, ?string $status, ?string $search, int $limit, int $offset): array { return []; }
                        public function countForUserList(User $user, ?string $status, ?string $search): int { return 0; }
                        public function countStatusBucketsForUser(User $user): array { return ['all' => 0, 'open' => 0, 'delivered' => 0, 'cancelled' => 0]; }
                        public function findRecentForAdmin(int $limit = 8): array { return []; }
                        public function findPendingPaymentForAdmin(int $limit = 10): array { return []; }
                        public function findFulfillmentQueue(int $limit = 30): array { return []; }
                        public function findForAdminList(?string $status, ?string $health, ?string $search, int $limit, int $offset): array { return []; }
                        public function countForAdminList(?string $status, ?string $health, ?string $search): int { return 0; }
                        public function getSummaryBetween(\DateTimeImmutable $from, \DateTimeImmutable $to): array { return ['count' => 0, 'totalCents' => 0]; }
                        public function getStatusCounts(): array { return []; }
                        public function countWithOperationalIssues(): int { return 0; }
                        public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array { return []; }
                    },
                    new class implements OrderEventRepositoryPort {
                        public function findByOrder(Order $order, string $direction = 'DESC'): array { return []; }
                        public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array { return []; }
                        public function count(array $criteria): int { return 0; }
                        public function findIssueEventsGroupedByOrders(array $orders): array { return []; }
                    },
                ),
                new OrderFormatter(
                    new OrderStatusLabelFormatter(),
                    new OrderItemFormatter(new ProductReviewFormatter()),
                    new OrderStatusWorkflow(),
                ),
            ),
        );
    }

    /** @return list<string> */
    private function loadVoucherCodes(string $statePath): array
    {
        if (!is_file($statePath)) {
            return [];
        }

        $payload = unserialize((string) file_get_contents($statePath), ['allowed_classes' => false]);

        return is_array($payload) ? array_values($payload) : [];
    }

    /** @return list<Appointment> */
    private function loadAppointments(string $statePath): array
    {
        if (!is_file($statePath)) {
            return [];
        }

        $payload = unserialize((string) file_get_contents($statePath), ['allowed_classes' => true]);

        return is_array($payload) ? $payload : [];
    }

    /** @return array{product:string,movements:list<array{delta:int,stockBefore:int,stockAfter:int}>} */
    private function readStockState(string $statePath): array
    {
        $payload = unserialize((string) file_get_contents($statePath), ['allowed_classes' => false]);
        self::assertIsArray($payload);

        return $payload;
    }

    /** @param array{product:string,movements:list<array{delta:int,stockBefore:int,stockAfter:int}>} $state */
    private function writeStockState(string $statePath, array $state): void
    {
        file_put_contents($statePath, serialize($state), LOCK_EX);
    }

    /** @param list<array<string, mixed>> $outputs */
    private function sortedDeltas(array $outputs): array
    {
        $deltas = array_map(static fn (array $output): int => (int) $output['delta'], $outputs);
        sort($deltas);

        return $deltas;
    }

    private function user(string $email): User
    {
        $user = new User($email, 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $this->setEntityId($user, 7);
        $user->setPassword('hashed');

        return $user;
    }

    private function setEntityId(object $entity, int $id): void
    {
        $reflection = new \ReflectionObject($entity);
        $property = $reflection->getProperty('id');
        $property->setValue($entity, $id);
    }

    private function awaitSignal(string $signalPath): void
    {
        $deadline = microtime(true) + 5;
        while (!is_file($signalPath) && microtime(true) < $deadline) {
            usleep(10_000);
        }

        self::assertFileExists($signalPath);
    }

    /** @param list<string> $paths */
    private function cleanupPaths(array $paths, string $workspace): void
    {
        foreach ($paths as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }

        if (is_dir($workspace)) {
            rmdir($workspace);
        }
    }
}

final class FileVoucherRepository implements VoucherRepositoryPort
{
    public function __construct(private readonly string $statePath)
    {
    }

    public function find(mixed $id, LockMode|int|null $lockMode = null, ?int $lockVersion = null): ?Voucher
    {
        return null;
    }

    public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array
    {
        return [];
    }

    public function count(array $criteria): int
    {
        return 0;
    }

    public function findOneByCode(?string $code): ?Voucher
    {
        $normalized = Voucher::normalizeCode((string) $code);
        if ('' === $normalized || !is_file($this->statePath)) {
            return null;
        }

        $codes = unserialize((string) file_get_contents($this->statePath), ['allowed_classes' => false]);
        if (!is_array($codes) || !in_array($normalized, $codes, true)) {
            return null;
        }

        return new Voucher('Existing', $normalized, Voucher::TYPE_PERCENT, 10);
    }

    public function save(Voucher $voucher): void
    {
    }

    public function findActiveForDate(\DateTimeImmutable $now): array
    {
        return [];
    }

    public function findByRecipientUserId(int $userId, int $limit = 20, int $offset = 0): array
    {
        return [];
    }

    public function countByRecipientUserId(int $userId): int
    {
        return 0;
    }
}

final class FileVoucherUnitOfWork implements UnitOfWork
{
    /** @var resource|null */
    private $lockHandle = null;
    private ?Voucher $voucher = null;

    public function __construct(
        private readonly string $statePath,
        private readonly string $lockPath,
        private readonly ?string $signalPath = null,
        private readonly int $sleepMicrosAfterLock = 0,
    ) {
    }

    public function persist(object $entity): void
    {
        if (!$entity instanceof Voucher) {
            throw new \InvalidArgumentException('Unexpected entity type.');
        }

        $this->voucher = $entity;
    }

    public function remove(object $entity): void
    {
    }

    public function flush(): void
    {
        if (!$this->voucher instanceof Voucher) {
            return;
        }

        $this->lockHandle = fopen($this->lockPath, 'c+');
        if (false === $this->lockHandle) {
            throw new \RuntimeException('Unable to open voucher lock file.');
        }

        flock($this->lockHandle, LOCK_EX);
        if (null !== $this->signalPath) {
            file_put_contents($this->signalPath, 'locked', LOCK_EX);
        }
        if ($this->sleepMicrosAfterLock > 0) {
            usleep($this->sleepMicrosAfterLock);
        }

        $codes = is_file($this->statePath)
            ? unserialize((string) file_get_contents($this->statePath), ['allowed_classes' => false])
            : [];
        $codes = is_array($codes) ? array_values($codes) : [];
        if (in_array($this->voucher->getCode(), $codes, true)) {
            flock($this->lockHandle, LOCK_UN);
            fclose($this->lockHandle);
            $this->lockHandle = null;

            throw new UniqueConstraintViolationException(
                new class('duplicate voucher code') extends \RuntimeException implements DriverException {
                    public function getSQLState(): ?string
                    {
                        return null;
                    }
                },
                null,
            );
        }

        $codes[] = $this->voucher->getCode();
        file_put_contents($this->statePath, serialize($codes), LOCK_EX);
        flock($this->lockHandle, LOCK_UN);
        fclose($this->lockHandle);
        $this->lockHandle = null;
    }
}

final class FileAppointmentRepository implements AppointmentRepositoryPort
{
    public function __construct(private readonly string $statePath)
    {
    }

    public function find(mixed $id, LockMode|int|null $lockMode = null, ?int $lockVersion = null): ?Appointment
    {
        return null;
    }

    public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array
    {
        return [];
    }

    public function count(array $criteria): int
    {
        return 0;
    }

    public function findBetween(\DateTimeImmutable $start, \DateTimeImmutable $end): array
    {
        return array_values(array_filter(
            $this->load(),
            static fn (Appointment $appointment): bool => $appointment->overlaps($start, $end),
        ));
    }

    public function findForUser(User $user, ?string $status = null, int $limit = 20, int $offset = 0): array
    {
        return [];
    }

    public function findUpcomingForUser(User $user, \DateTimeImmutable $now, int $limit = 20, int $offset = 0): array
    {
        return [];
    }

    public function countUpcomingForUser(User $user, \DateTimeImmutable $now): int
    {
        return 0;
    }

    public function findPastForUser(User $user, \DateTimeImmutable $now, int $limit = 20, int $offset = 0): array
    {
        return [];
    }

    public function countPastForUser(User $user, \DateTimeImmutable $now): int
    {
        return 0;
    }

    public function append(Appointment $appointment): void
    {
        $appointments = $this->load();
        $appointments[] = $appointment;
        file_put_contents($this->statePath, serialize($appointments), LOCK_EX);
    }

    /** @return list<Appointment> */
    private function load(): array
    {
        if (!is_file($this->statePath)) {
            return [];
        }

        $payload = unserialize((string) file_get_contents($this->statePath), ['allowed_classes' => true]);

        return is_array($payload) ? array_values(array_filter($payload, static fn (mixed $item): bool => $item instanceof Appointment)) : [];
    }
}

final class FileWorkingDayConfigurationRepository implements WorkingDayConfigurationRepositoryPort
{
    /** @var resource|null */
    private $lockHandle = null;

    public function __construct(
        private readonly string $lockPath,
        private readonly ?string $signalPath = null,
        private readonly int $sleepMicrosAfterLock = 0,
    ) {
    }

    public function findOneByDay(int $dayOfWeek): ?WorkingDayConfiguration
    {
        return $this->configuration($dayOfWeek);
    }

    public function findOneByDayForUpdate(int $dayOfWeek): ?WorkingDayConfiguration
    {
        if (!is_resource($this->lockHandle)) {
            $this->lockHandle = fopen($this->lockPath, 'c+');
            if (false === $this->lockHandle) {
                throw new \RuntimeException('Unable to open appointment lock file.');
            }
            flock($this->lockHandle, LOCK_EX);
            if (null !== $this->signalPath) {
                file_put_contents($this->signalPath, 'locked', LOCK_EX);
            }
            if ($this->sleepMicrosAfterLock > 0) {
                usleep($this->sleepMicrosAfterLock);
            }
        }

        return $this->configuration($dayOfWeek);
    }

    public function findAllOrdered(): array
    {
        $days = [];
        for ($day = 0; $day <= 6; ++$day) {
            $days[] = $this->configuration($day);
        }

        return $days;
    }

    public function releaseLock(): void
    {
        if (!is_resource($this->lockHandle)) {
            return;
        }

        flock($this->lockHandle, LOCK_UN);
        fclose($this->lockHandle);
        $this->lockHandle = null;
    }

    private function configuration(int $dayOfWeek): WorkingDayConfiguration
    {
        return new WorkingDayConfiguration(
            $dayOfWeek,
            true,
            new \DateTimeImmutable('09:00'),
            new \DateTimeImmutable('18:00'),
        );
    }
}

final class FileAppointmentUnitOfWork implements UnitOfWork
{
    private ?Appointment $appointment = null;

    public function __construct(private readonly FileAppointmentRepository $appointments)
    {
    }

    public function persist(object $entity): void
    {
        if (!$entity instanceof Appointment) {
            throw new \InvalidArgumentException('Unexpected entity type.');
        }

        $this->appointment = $entity;
    }

    public function remove(object $entity): void
    {
    }

    public function flush(): void
    {
        if ($this->appointment instanceof Appointment) {
            $this->appointments->append($this->appointment);
            $this->appointment = null;
        }
    }
}

final class FileProductCatalogRepository implements ProductCatalogRepository
{
    /** @var resource|null */
    private $lockHandle = null;
    private ?Product $lockedProduct = null;

    public function __construct(
        private readonly string $statePath,
        private readonly string $lockPath,
        private readonly ?string $signalPath = null,
        private readonly int $sleepMicrosAfterLock = 0,
    ) {
    }

    public function findProduct(int $id): ?Product
    {
        return $this->findForUpdate($id);
    }

    public function findForUpdate(int $id): ?Product
    {
        if (!is_resource($this->lockHandle)) {
            $this->lockHandle = fopen($this->lockPath, 'c+');
            if (false === $this->lockHandle) {
                throw new \RuntimeException('Unable to open stock lock file.');
            }
            flock($this->lockHandle, LOCK_EX);
            if (null !== $this->signalPath) {
                file_put_contents($this->signalPath, 'locked', LOCK_EX);
            }
            if ($this->sleepMicrosAfterLock > 0) {
                usleep($this->sleepMicrosAfterLock);
            }
        }

        $state = $this->state();
        $product = unserialize($state['product'], ['allowed_classes' => true]);
        if (!$product instanceof Product || $product->getId() !== $id) {
            return null;
        }

        $this->lockedProduct = $product;

        return $product;
    }

    public function persistLockedProduct(): void
    {
        if (!$this->lockedProduct instanceof Product) {
            return;
        }

        $state = $this->state();
        $state['product'] = serialize($this->lockedProduct);
        file_put_contents($this->statePath, serialize($state), LOCK_EX);
    }

    public function releaseLock(): void
    {
        if (!is_resource($this->lockHandle)) {
            return;
        }

        flock($this->lockHandle, LOCK_UN);
        fclose($this->lockHandle);
        $this->lockHandle = null;
        $this->lockedProduct = null;
    }

    public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array { return []; }
    public function findByVariantGroupOrdered(string $variantGroup): array { return []; }
    public function findPublishedByVariantGroupOrdered(string $variantGroup): array { return []; }
    public function countByBrand(\App\Module\Catalog\Domain\Entity\Brand $brand): int { return 0; }
    public function clearBrand(\App\Module\Catalog\Domain\Entity\Brand $brand): void {}
    public function existsWithSku(string $sku, ?int $excludeId = null): bool { return false; }
    public function existsWithSlug(string $slug, ?int $excludeId = null): bool { return false; }
    public function countLowStock(int $threshold = 3): int { return 0; }
    public function findLowStock(int $threshold = 3, int $limit = 8): array { return []; }
    public function findAllForAdmin(\App\Module\Catalog\Application\Query\ProductAdminCriteria $criteria): array { return []; }
    public function countForAdmin(\App\Module\Catalog\Application\Query\ProductAdminCriteria $criteria): int { return 0; }
    public function findPublished(\App\Module\Catalog\Application\Query\ProductCatalogCriteria $criteria): array { return []; }
    public function findPublishedListProjection(\App\Module\Catalog\Application\Query\ProductCatalogCriteria $criteria): array { return []; }
    public function countPublished(\App\Module\Catalog\Application\Query\ProductCatalogCriteria $criteria): int { return 0; }
    public function collectPublishedFacets(\App\Module\Catalog\Application\Query\ProductCatalogCriteria $criteria): array { return []; }
    public function findOnePublishedBySlug(string $slug): ?Product { return null; }

    /** @return array{product:string,movements:list<array{delta:int,stockBefore:int,stockAfter:int}>} */
    private function state(): array
    {
        $payload = unserialize((string) file_get_contents($this->statePath), ['allowed_classes' => false]);

        return is_array($payload) ? $payload : ['product' => serialize(null), 'movements' => []];
    }
}

final class FileStockPersistence implements OperationsPersistence
{
    /** @var list<object> */
    private array $entities = [];

    public function __construct(
        private readonly FileProductCatalogRepository $products,
        private readonly string $statePath,
    ) {
    }

    public function persist(object $entity): void
    {
        $this->entities[] = $entity;
    }

    public function flush(): void
    {
        $this->products->persistLockedProduct();

        if ([] === $this->entities) {
            return;
        }

        $state = unserialize((string) file_get_contents($this->statePath), ['allowed_classes' => false]);
        $state = is_array($state) ? $state : ['product' => serialize(null), 'movements' => []];

        foreach ($this->entities as $entity) {
            if ($entity instanceof \App\Module\Catalog\Domain\Entity\StockMovement) {
                $state['movements'][] = [
                    'delta' => $entity->getDelta(),
                    'stockBefore' => $entity->getStockBefore(),
                    'stockAfter' => $entity->getStockAfter(),
                ];
            }
        }

        file_put_contents($this->statePath, serialize($state), LOCK_EX);
        $this->entities = [];
    }
}

final class FileLockTransactionManager implements TransactionManager
{
    public function __construct(private readonly object $lockOwner)
    {
    }

    public function transactional(\Closure $operation): mixed
    {
        try {
            return $operation();
        } finally {
            if (method_exists($this->lockOwner, 'releaseLock')) {
                $this->lockOwner->releaseLock();
            }
        }
    }
}
