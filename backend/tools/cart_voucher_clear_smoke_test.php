<?php

declare(strict_types=1);

require dirname(__DIR__).'/vendor/autoload.php';

use App\Module\Cart\Application\Port\CartSessionRepositoryPort;
use App\Module\Cart\Application\Provider\CartSessionProvider;
use App\Module\Cart\Application\Workflow\CartVoucherService;
use App\Module\Cart\Domain\Entity\CartSession;
use App\Module\User\Domain\Entity\User;
use App\Module\Voucher\Application\Calculator\VoucherEngine;
use App\Module\Voucher\Application\Port\VoucherRepositoryPort;
use App\Module\Voucher\Application\Projection\VoucherFormatter;
use App\Module\Voucher\Domain\Entity\Voucher;
use App\Shared\Application\LockMode;
use App\Shared\Application\UnitOfWork;

/**
 * @var CartSessionRepositoryPort $cartSessionRepository
 */
$cartSessionRepository = new class implements CartSessionRepositoryPort {
    /**
     * @var array<string, CartSession>
     */
    private array $cartsByToken = [];

    public function seed(CartSession $cart): void
    {
        $this->cartsByToken[$cart->getToken()] = $cart;
    }

    public function findOneByToken(string $token): ?CartSession
    {
        return $this->cartsByToken[$token] ?? null;
    }

    public function findForUpdate(int $id): ?CartSession
    {
        foreach ($this->cartsByToken as $cart) {
            if (null !== $cart->getId() && $cart->getId() === $id) {
                return $cart;
            }
        }

        return null;
    }

    public function findOneByUser(User $user): ?CartSession
    {
        foreach ($this->cartsByToken as $cart) {
            if (null !== $cart->getUser() && null !== $cart->getUser()->getId() && $cart->getUser()->getId() === $user->getId()) {
                return $cart;
            }
        }

        return null;
    }

    public function findOneByUserId(int $userId): ?CartSession
    {
        foreach ($this->cartsByToken as $cart) {
            if (null !== $cart->getUser() && null !== $cart->getUser()->getId() && $userId === $cart->getUser()->getId()) {
                return $cart;
            }
        }

        return null;
    }

    public function clearUnitOfWork(): void
    {
    }
};

$unitOfWork = new class implements UnitOfWork {
    public bool $flushed = false;

    public function persist(object $entity): void
    {
    }

    public function remove(object $entity): void
    {
    }

    public function flush(): void
    {
        $this->flushed = true;
    }
};

$voucherEngine = new VoucherEngine(
    new class implements VoucherRepositoryPort {
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

        public function findActiveForDate(\DateTimeImmutable $now): array
        {
            return [];
        }

        public function findOneByCode(?string $code): ?Voucher
        {
            return null;
        }

        public function save(Voucher $voucher): void
        {
        }

        public function findByRecipientUserId(int $userId, int $limit = 20, int $offset = 0): array
        {
            return [];
        }

        public function countByRecipientUserId(int $userId): int
        {
            return 0;
        }
    },
    new VoucherFormatter(),
);

$cart = new CartSession('test-cart-token');
$cart->setVoucherCode('BON-TEST');
$cartSessionRepository->seed($cart);

$provider = new CartSessionProvider($cartSessionRepository, $unitOfWork);
$service = new CartVoucherService($provider, $voucherEngine, $unitOfWork);
$result = $service->clear('test-cart-token');

if ($result !== $cart) {
    fwrite(STDERR, "Smoke test failed: service returned a different cart instance\n");
    exit(1);
}

if (null !== $result->getVoucherCode()) {
    fwrite(STDERR, "Smoke test failed: voucher code was not cleared\n");
    exit(1);
}

if (!$unitOfWork->flushed) {
    fwrite(STDERR, "Smoke test failed: unit of work was not flushed\n");
    exit(1);
}

if (null !== $cartSessionRepository->findOneByToken('test-cart-token')?->getVoucherCode()) {
    fwrite(STDERR, "Smoke test failed: repository still exposes a voucher code\n");
    exit(1);
}

echo "Smoke test OK: cart voucher removal works.\n";
