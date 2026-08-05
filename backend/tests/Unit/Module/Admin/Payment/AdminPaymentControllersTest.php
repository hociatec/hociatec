<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Admin\Payment;

use App\Module\Admin\UI\Payment\Controller\ListPaymentsController;
use App\Module\Admin\UI\Payment\Controller\ShowPaymentController;
use App\Module\Admin\Application\Payment\Projection\AdminPaymentFormatter;
use App\Module\Admin\Application\Payment\Provider\StripePaymentDetailsProvider;
use App\Module\Order\Domain\Entity\OrderCheckoutSession;
use App\Module\Order\Infrastructure\Repository\OrderCheckoutSessionRepository;
use App\Module\Order\Application\Workflow\StripeApiClient;
use App\Module\Order\Application\Workflow\StripeCheckoutSessionSyncService;
use App\Module\User\Domain\Entity\User;
use App\Shared\Infrastructure\Doctrine\DoctrineUnitOfWork;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class AdminPaymentControllersTest extends TestCase
{
    private ?EntityManager $entityManager = null;

    protected function tearDown(): void
    {
        $this->entityManager?->close();
        $this->entityManager = null;
    }

    public function testPaymentControllersListShowAndStripeDetailsFallback(): void
    {
        $payment = $this->persistPayment();
        $formatter = new AdminPaymentFormatter();
        $sync = new StripeCheckoutSessionSyncService($this->payments(), new StripeApiClient(''), new DoctrineUnitOfWork($this->entityManager()));

        $list = new ListPaymentsController($this->payments(), $sync, $formatter);
        $allPayload = $this->payload($list(Request::create('/?status=all')));
        self::assertSame('stripe-session-admin', $allPayload['data']['items'][0]['stripeSessionId']);

        $filteredPayload = $this->payload($list(Request::create('/?status=paid&q=admin-pay')));
        self::assertSame('admin-pay@example.test', $filteredPayload['data']['items'][0]['customerEmail']);

        $stripeDetails = new StripePaymentDetailsProvider(new StripeApiClient(''), $formatter);
        self::assertSame(['error' => 'Détails Stripe indisponibles.'], $stripeDetails->provide($payment));

        self::assertSame('Carte refusée', $formatter->failureCodeLabel('card_declined'));
        self::assertSame('Paiement refusé', $formatter->stripeEventLabel('payment_intent.payment_failed'));
        self::assertSame('Ouverte', $formatter->stripeCheckoutStatusLabel('open'));
        self::assertSame('requires_capture', $formatter->stripePaymentStatusLabel('requires_capture'));
        self::assertSame('paid', $formatter->statusOptions()[1]['value']);

        $show = new ShowPaymentController($this->payments(), $sync, $formatter, $stripeDetails);
        self::assertSame(404, $show(999)->getStatusCode());
        $detail = $this->payload($show((int) $payment->getId()));
        self::assertSame('stripe-session-admin', $detail['data']['payment']['stripeSessionId']);
        self::assertSame('Détails Stripe indisponibles.', $detail['data']['liveStripe']['error']);
    }

    private function persistPayment(): OrderCheckoutSession
    {
        $user = new User('admin-pay@example.test', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $user->setPassword('hashed');
        $payment = (new OrderCheckoutSession('pay-token-admin', $user, 'cart-token-admin', 12, 'stripe-session-admin', 'https://checkout.test'))
            ->setTotalPriceCents(12900)
            ->markPaid('pi_admin', 'paid', 'checkout.session.completed')
            ->setOrderId(null);
        $this->entityManager()->persist($user);
        $this->entityManager()->persist($payment);
        $this->entityManager()->flush();

        return $payment;
    }

    private function entityManager(): EntityManager
    {
        if ($this->entityManager instanceof EntityManager) {
            return $this->entityManager;
        }

        $config = ORMSetup::createAttributeMetadataConfiguration([__DIR__.'/../../../../src'], true);
        $config->setNamingStrategy(new UnderscoreNamingStrategy());
        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true], $config);
        $entityManager = new EntityManager($connection, $config);
        (new SchemaTool($entityManager))->createSchema([
            $entityManager->getClassMetadata(User::class),
            $entityManager->getClassMetadata(OrderCheckoutSession::class),
        ]);

        return $this->entityManager = $entityManager;
    }

    private function registry(): ManagerRegistry
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($this->entityManager());

        return $registry;
    }

    private function payments(): OrderCheckoutSessionRepository
    {
        return new OrderCheckoutSessionRepository($this->registry());
    }

    /** @return array<string,mixed> */
    private function payload(Response $response): array
    {
        return json_decode($response->getContent() ?: '{}', true, 512, JSON_THROW_ON_ERROR);
    }
}
