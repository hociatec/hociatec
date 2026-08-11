<?php

declare(strict_types=1);

namespace App\Module\Auth\Infrastructure\Seed;

use App\DataFixtures\E2eUserFixtures;
use App\Module\Appointment\Domain\Entity\Appointment;
use App\Module\Appointment\Domain\Entity\Prestation;
use App\Module\BetaTest\Domain\Entity\BetaTesterProfile;
use App\Module\Catalog\Domain\Entity\Product;
use App\Module\Order\Domain\Entity\Order;
use App\Module\Order\Domain\Entity\OrderItem;
use App\Module\Rating\Domain\Entity\ProductRating;
use App\Module\User\Application\Port\UserPasswordHasher;
use App\Module\User\Domain\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\RateLimiter\RateLimiterFactory;

class E2eDataSeeder
{
    private const DEFAULT_BIRTH_DATE = '1990-01-01';
    private const DEFAULT_PHONE = '0600000000';
    private const DEFAULT_GENDER = 'other';
    private const DEFAULT_NAME = 'Client E2E';
    private const DEFAULT_ADDRESS = '10 rue de test';
    private const DEFAULT_POSTAL_CODE = '75001';
    private const DEFAULT_CITY = 'Paris';
    private const PENDING_ORDER_NUMBER_CHROMIUM = 'ORD-E2E-PENDING-CHROMIUM';
    private const PENDING_ORDER_NUMBER_MOBILE = 'ORD-E2E-PENDING-MOBILE';
    private const CONFIRMED_ORDER_NUMBER = 'ORD-E2E-CONFIRMED';
    private const DELIVERED_ORDER_NUMBER_CHROMIUM = 'ORD-E2E-DELIVERED-CHROMIUM';
    private const DELIVERED_ORDER_NUMBER_MOBILE = 'ORD-E2E-DELIVERED-MOBILE';
    private const ADMIN_PENDING_ORDER_NUMBER = 'ORD-PLAY-ADMIN-PENDING';
    private const ADMIN_CONFIRMED_ORDER_NUMBER = 'ORD-PLAY-ADMIN-CONFIRMED';
    private const APPOINTMENT_PRESTATION_NAME = 'Diagnostic e2e prioritaire';
    private const UPCOMING_APPOINTMENT_START_CHROMIUM = '2026-08-20 09:00:00';
    private const UPCOMING_APPOINTMENT_START_MOBILE = '2026-08-21 14:00:00';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasher $passwordHasher,
        #[Autowire(service: 'limiter.beta_report_create')]
        private readonly RateLimiterFactory $betaReportCreateLimiter,
    ) {
    }

    public function seed(): void
    {
        $client = $this->seedUsers();
        $this->seedOrders($client);
        $this->seedAppointments($client);
        $this->seedBetaTestData($client);

        $this->entityManager->flush();
    }

    private function seedUsers(): User
    {
        $client = $this->upsertUser(E2eUserFixtures::CLIENT_EMAIL, ['ROLE_USER'], 'Client', 'E2E');
        $this->upsertUser(E2eUserFixtures::ADMIN_EMAIL, ['ROLE_ADMIN'], 'Admin', 'E2E');

        return $client;
    }

    private function seedOrders(User $client): void
    {
        foreach ($this->standardOrders() as [$number, $status, $totalPriceCents]) {
            $this->upsertOrder($number, $client, $status, $totalPriceCents);
        }

        $this->upsertDeliveredOrderWithReviewableItem($client, self::DELIVERED_ORDER_NUMBER_CHROMIUM);
        $this->upsertDeliveredOrderWithReviewableItem($client, self::DELIVERED_ORDER_NUMBER_MOBILE);
    }

    private function seedAppointments(User $client): void
    {
        $prestation = $this->upsertAppointmentPrestation();
        foreach ($this->upcomingAppointmentStarts() as $startAt) {
            $this->upsertUpcomingAppointment($client, $prestation, $startAt);
        }
    }

    private function seedBetaTestData(User $client): void
    {
        $this->upsertBetaProfile($client);
        $this->resetBetaReportRateLimit($client);
    }

    /**
     * @return list<array{0:string,1:string,2:int}>
     */
    private function standardOrders(): array
    {
        return [
            [self::PENDING_ORDER_NUMBER_CHROMIUM, Order::STATUS_PENDING, 129900],
            [self::PENDING_ORDER_NUMBER_MOBILE, Order::STATUS_PENDING, 129900],
            [self::CONFIRMED_ORDER_NUMBER, Order::STATUS_CONFIRMED, 99900],
            [self::ADMIN_PENDING_ORDER_NUMBER, Order::STATUS_PENDING, 149900],
            [self::ADMIN_CONFIRMED_ORDER_NUMBER, Order::STATUS_CONFIRMED, 179900],
        ];
    }

    /**
     * @return list<\DateTimeImmutable>
     */
    private function upcomingAppointmentStarts(): array
    {
        return [
            new \DateTimeImmutable(self::UPCOMING_APPOINTMENT_START_CHROMIUM),
            new \DateTimeImmutable(self::UPCOMING_APPOINTMENT_START_MOBILE),
        ];
    }

    /**
     * @param list<string> $roles
     */
    private function upsertUser(
        string $email,
        array $roles,
        string $firstName,
        string $lastName,
    ): User {
        /** @var User|null $user */
        $user = $this->entityManager->createQueryBuilder()
            ->select('u')
            ->from(User::class, 'u')
            ->andWhere('LOWER(u.identity.email) = LOWER(:email)')
            ->setParameter('email', $email)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
        if (!$user instanceof User) {
            $user = new User(
                $email,
                $firstName,
                $lastName,
                new \DateTimeImmutable(self::DEFAULT_BIRTH_DATE),
                self::DEFAULT_PHONE,
                self::DEFAULT_GENDER,
            );
            $this->entityManager->persist($user);
        }

        $this->applyUserIdentity($user, $email, $firstName, $lastName, $roles);

        return $user;
    }

    private function upsertOrder(
        string $number,
        User $user,
        string $status,
        int $totalPriceCents,
    ): void {
        /** @var Order|null $order */
        $order = $this->entityManager->getRepository(Order::class)->findOneBy(['number' => $number]);
        if (!$order instanceof Order) {
            $order = new Order($number, $user);
            $this->entityManager->persist($order);
        }

        $order
            ->setUser($user)
            ->setStatus($status)
            ->setSubtotalPriceCents($totalPriceCents)
            ->setDiscountAmountCents(0)
            ->setTotalPriceCents($totalPriceCents)
            ->setDeliveryStatus(Order::DELIVERY_STATUS_PREPARING)
            ->setInvoiceStatus(Order::INVOICE_STATUS_ISSUED)
            ->setInvoicedAt(new \DateTimeImmutable());

        $this->applyOrderAddresses($order, $user);
    }

    private function upsertDeliveredOrderWithReviewableItem(User $user, string $number): void
    {
        $product = $this->findFirstCatalogProduct();
        if (!$product instanceof Product) {
            return;
        }

        /** @var Order|null $order */
        $order = $this->entityManager->getRepository(Order::class)->findOneBy(['number' => $number]);
        if (!$order instanceof Order) {
            $order = new Order($number, $user);
            $this->entityManager->persist($order);
        }

        $this->resetOrderItemsAndRatings($order);

        $item = (new OrderItem($product->getName(), $product->getSku(), $product->getPriceCents(), 1))
            ->setProduct($product)
            ->replaceLineTotals($product->getPriceCents(), 0, $product->getPriceCents());

        $order
            ->setUser($user)
            ->setStatus(Order::STATUS_DELIVERED)
            ->setSubtotalPriceCents($product->getPriceCents())
            ->setDiscountAmountCents(0)
            ->setTotalPriceCents($product->getPriceCents())
            ->setDeliveryStatus(Order::DELIVERY_STATUS_DELIVERED)
            ->setDeliveryCarrier('Chronopost')
            ->setDeliveryTrackingNumber('E2E-TRACK-DELIVERED')
            ->setDeliveryTrackingUrl('https://example.test/tracking/e2e-delivered')
            ->setDeliveryEstimatedAt(new \DateTimeImmutable('2026-08-06 10:00:00'))
            ->setDeliveryShippedAt(new \DateTimeImmutable('2026-08-05 10:00:00'))
            ->setDeliveryDeliveredAt(new \DateTimeImmutable('2026-08-06 14:00:00'))
            ->setInvoiceStatus(Order::INVOICE_STATUS_ISSUED)
            ->setInvoicedAt(new \DateTimeImmutable('2026-08-05 09:00:00'))
            ->addItem($item);

        $this->applyOrderAddresses($order, $user);
    }

    private function findFirstCatalogProduct(): ?Product
    {
        /** @var Product|null $product */
        $product = $this->entityManager->createQueryBuilder()
            ->select('p')
            ->from(Product::class, 'p')
            ->orderBy('p.id', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $product;
    }

    private function upsertAppointmentPrestation(): Prestation
    {
        /** @var Prestation|null $prestation */
        $prestation = $this->entityManager->createQueryBuilder()
            ->select('p')
            ->from(Prestation::class, 'p')
            ->andWhere('p.name = :name')
            ->setParameter('name', self::APPOINTMENT_PRESTATION_NAME)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$prestation instanceof Prestation) {
            $prestation = new Prestation(self::APPOINTMENT_PRESTATION_NAME, 45, 6900);
            $this->entityManager->persist($prestation);
        }

        $prestation
            ->setName(self::APPOINTMENT_PRESTATION_NAME)
            ->setDurationMinutes(45)
            ->setPriceCents(6900);

        return $prestation;
    }

    private function upsertUpcomingAppointment(User $user, Prestation $prestation, \DateTimeImmutable $startAt): void
    {
        /** @var Appointment|null $appointment */
        $appointment = $this->entityManager->createQueryBuilder()
            ->select('a')
            ->from(Appointment::class, 'a')
            ->where('a.user = :user')
            ->andWhere('a.startAt = :startAt')
            ->setParameter('user', $user)
            ->setParameter('startAt', $startAt)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$appointment instanceof Appointment) {
            $appointment = new Appointment($user, $prestation, $startAt);
            $this->entityManager->persist($appointment);
        }

        $appointment
            ->setStartAt($startAt)
            ->setStatus(Appointment::STATUS_CONFIRMED);
    }

    private function resetOrderItemsAndRatings(Order $order): void
    {
        $items = $order->getItems()->toArray();

        if ([] === $items) {
            return;
        }

        $ratings = $this->entityManager->createQueryBuilder()
            ->select('r')
            ->from(ProductRating::class, 'r')
            ->join('r.orderItem', 'oi')
            ->where('oi IN (:items)')
            ->setParameter('items', $items)
            ->getQuery()
            ->getResult();

        foreach ($ratings as $rating) {
            if ($rating instanceof ProductRating) {
                $this->entityManager->remove($rating);
            }
        }

        foreach ($items as $item) {
            $order->removeItem($item);
            $this->entityManager->remove($item);
        }
    }

    private function resetBetaReportRateLimit(User $user): void
    {
        $key = 'beta_report_create:app_module_betatest_ui_createbugreport__invoke:user:'.$user->getEmail();
        $this->betaReportCreateLimiter->create($key)->reset();
    }

    /**
     * @param list<string> $roles
     */
    private function applyUserIdentity(User $user, string $email, string $firstName, string $lastName, array $roles): void
    {
        $user
            ->setFirstName($firstName)
            ->setLastName($lastName)
            ->setEmail($email)
            ->setPhoneNumber(self::DEFAULT_PHONE)
            ->setBirthDate(new \DateTimeImmutable(self::DEFAULT_BIRTH_DATE))
            ->setGender(self::DEFAULT_GENDER)
            ->setRoles($roles)
            ->setIsVerified(true)
            ->setPassword($this->passwordHasher->hashPassword($user, E2eUserFixtures::PASSWORD));
    }

    private function applyOrderAddresses(Order $order, User $user): void
    {
        $order
            ->setShippingName(self::DEFAULT_NAME)
            ->setShippingAddress(self::DEFAULT_ADDRESS)
            ->setShippingPostalCode(self::DEFAULT_POSTAL_CODE)
            ->setShippingCity(self::DEFAULT_CITY)
            ->setBillingName(self::DEFAULT_NAME)
            ->setBillingAddress(self::DEFAULT_ADDRESS)
            ->setBillingPostalCode(self::DEFAULT_POSTAL_CODE)
            ->setBillingCity(self::DEFAULT_CITY)
            ->setBillingEmail($user->getEmail());
    }

    private function upsertBetaProfile(User $user): void
    {
        /** @var BetaTesterProfile|null $profile */
        $profile = $this->entityManager->getRepository(BetaTesterProfile::class)->findOneBy(['user' => $user]);
        if (!$profile instanceof BetaTesterProfile) {
            $profile = new BetaTesterProfile(
                $user,
                ['weekdays'],
                'Profil bêta E2E seedé pour valider le parcours complet.',
                'none',
                'basic',
                'none',
                'none',
                ['none'],
                ['windows'],
                ['chrome'],
                ['bugs'],
                new \DateTimeImmutable('2026-08-10 09:00:00'),
                '2026-07-26',
            );
            $this->entityManager->persist($profile);
        } else {
            $profile->update(
                ['weekdays'],
                'Profil bêta E2E seedé pour valider le parcours complet.',
                'none',
                'basic',
                'none',
                'none',
                ['none'],
                ['windows'],
                ['chrome'],
                ['bugs'],
            );
        }

        $profile->setStatus(BetaTesterProfile::STATUS_PENDING);
    }
}
