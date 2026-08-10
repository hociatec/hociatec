<?php

declare(strict_types=1);

namespace App\Module\Auth\Infrastructure\Command;

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
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;

#[AsCommand(name: 'app:e2e:seed', description: 'Seed stable end-to-end users and orders for Playwright runs.')]
final class SeedE2eDataCommand extends Command
{
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
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $client = $this->upsertUser(
            E2eUserFixtures::CLIENT_EMAIL,
            ['ROLE_USER'],
            'Client',
            'E2E',
        );
        $this->upsertUser(
            E2eUserFixtures::ADMIN_EMAIL,
            ['ROLE_ADMIN'],
            'Admin',
            'E2E',
        );

        $this->upsertOrder(
            self::PENDING_ORDER_NUMBER_CHROMIUM,
            $client,
            Order::STATUS_PENDING,
            129900,
        );
        $this->upsertOrder(
            self::PENDING_ORDER_NUMBER_MOBILE,
            $client,
            Order::STATUS_PENDING,
            129900,
        );
        $this->upsertOrder(
            self::CONFIRMED_ORDER_NUMBER,
            $client,
            Order::STATUS_CONFIRMED,
            99900,
        );
        $this->upsertDeliveredOrderWithReviewableItem($client, self::DELIVERED_ORDER_NUMBER_CHROMIUM);
        $this->upsertDeliveredOrderWithReviewableItem($client, self::DELIVERED_ORDER_NUMBER_MOBILE);
        $this->upsertOrder(
            self::ADMIN_PENDING_ORDER_NUMBER,
            $client,
            Order::STATUS_PENDING,
            149900,
        );
        $this->upsertOrder(
            self::ADMIN_CONFIRMED_ORDER_NUMBER,
            $client,
            Order::STATUS_CONFIRMED,
            179900,
        );
        $prestation = $this->upsertAppointmentPrestation();
        $this->upsertUpcomingAppointment($client, $prestation, new \DateTimeImmutable(self::UPCOMING_APPOINTMENT_START_CHROMIUM));
        $this->upsertUpcomingAppointment($client, $prestation, new \DateTimeImmutable(self::UPCOMING_APPOINTMENT_START_MOBILE));
        $this->upsertBetaProfile($client);
        $this->resetBetaReportRateLimit($client);

        $this->entityManager->flush();

        $output->writeln('<info>E2E users and orders seeded.</info>');

        return Command::SUCCESS;
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
                new \DateTimeImmutable('1990-01-01'),
                '0600000000',
                'other',
            );
            $this->entityManager->persist($user);
        }

        $user
            ->setFirstName($firstName)
            ->setLastName($lastName)
            ->setEmail($email)
            ->setPhoneNumber('0600000000')
            ->setBirthDate(new \DateTimeImmutable('1990-01-01'))
            ->setGender('other')
            ->setRoles($roles)
            ->setIsVerified(true)
            ->setPassword($this->passwordHasher->hashPassword($user, E2eUserFixtures::PASSWORD));

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
            ->setInvoicedAt(new \DateTimeImmutable())
            ->setShippingName('Client E2E')
            ->setShippingAddress('10 rue de test')
            ->setShippingPostalCode('75001')
            ->setShippingCity('Paris')
            ->setBillingName('Client E2E')
            ->setBillingAddress('10 rue de test')
            ->setBillingPostalCode('75001')
            ->setBillingCity('Paris')
            ->setBillingEmail($user->getEmail());
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
            ->setShippingName('Client E2E')
            ->setShippingAddress('10 rue de test')
            ->setShippingPostalCode('75001')
            ->setShippingCity('Paris')
            ->setBillingName('Client E2E')
            ->setBillingAddress('10 rue de test')
            ->setBillingPostalCode('75001')
            ->setBillingCity('Paris')
            ->setBillingEmail($user->getEmail())
            ->addItem($item);
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

        if ($items === []) {
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
            if ($item instanceof OrderItem) {
                $order->removeItem($item);
                $this->entityManager->remove($item);
            }
        }
    }

    private function resetBetaReportRateLimit(User $user): void
    {
        $key = 'beta_report_create:app_module_betatest_ui_createbugreport__invoke:user:'.$user->getEmail();
        $this->betaReportCreateLimiter->create($key)->reset();
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
