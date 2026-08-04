<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Misc;

use App\Module\Admin\Application\Marketing\Service\CreateEmailTemplateHandler;
use App\Module\Admin\Application\Marketing\Service\DeleteEmailTemplateHandler;
use App\Module\Admin\Application\Marketing\Service\UpdateEmailTemplateHandler;
use App\Module\Admin\UI\Payment\Controller\ListPaymentMetadataController;
use App\Module\Admin\Application\Payment\Projection\AdminPaymentFormatter;
use App\Module\Audit\Domain\Entity\AuditRequest;
use App\Module\Audit\Domain\Entity\AuditType;
use App\Module\Audit\Application\Service\AuditEventLogger;
use App\Module\Catalog\UI\Controller\PublicApi\ListProductsController;
use App\Module\Catalog\Domain\Entity\Brand;
use App\Module\Catalog\Domain\Entity\Category;
use App\Module\Catalog\Domain\Entity\Product;
use App\Module\Catalog\Infrastructure\Http\ProductSearchRequestMapper;
use App\Module\Catalog\Infrastructure\Repository\ProductRepository;
use App\Module\Catalog\Application\Service\ProductCatalogSearchProvider;
use App\Module\Catalog\Application\Service\ProductQueryService;
use App\Module\Marketing\Domain\Entity\EmailTemplate;
use App\Module\Order\Domain\Entity\Order;
use App\Module\Order\Domain\Entity\OrderCheckoutSession;
use App\Module\Order\Application\Service\OrderPersistence;
use App\Module\Order\Application\Service\OrderWorkflowService;
use App\Module\User\Domain\Entity\User;
use App\Infrastructure\Http\InvalidJsonPayloadException;
use App\Infrastructure\Http\JsonPayload;
use App\Shared\Infrastructure\Doctrine\DoctrineUnitOfWork;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class HttpCatalogAndManagerBatchTest extends TestCase
{
    public function testJsonPayloadDecodeHandlesValidAndInvalidPayloads(): void
    {
        self::assertSame([], JsonPayload::decode(new Request(content: '   ')));
        self::assertSame(['name' => 'Ada'], JsonPayload::decode(new Request(content: '{"name":"Ada"}')));

        try {
            JsonPayload::decode(new Request(content: '{"name":'));
            self::fail('Expected invalid JSON exception.');
        } catch (InvalidJsonPayloadException $exception) {
            self::assertSame('Payload JSON invalide.', $exception->getMessage());
            self::assertSame(Response::HTTP_BAD_REQUEST, $exception->getStatusCode());
        }

        try {
            JsonPayload::decode(new Request(content: '[1,2,3]'));
            self::fail('Expected object payload exception.');
        } catch (InvalidJsonPayloadException $exception) {
            self::assertSame('Le payload JSON doit être un objet.', $exception->getMessage());
        }

        try {
            JsonPayload::decode(new Request(content: str_repeat('a', 1_048_577)));
            self::fail('Expected oversized payload exception.');
        } catch (InvalidJsonPayloadException $exception) {
            self::assertSame('Payload trop volumineux.', $exception->getMessage());
        }

        $this->coverPrivateConstructor(JsonPayload::class);
    }

    public function testCatalogRequestMapperAndListProductsController(): void
    {
        $category = new Category('Phones', 'phones');
        $brand = new Brand('Apple');
        $this->setId($category, 5);
        $this->setId($brand, 9);

        $product = (new Product('iPhone', 'iphone', 'IP-1', 'Desc', 199900, 4, $category))
            ->setShortDescription('Short')
            ->setSellingType('rental')
            ->setBrandReference($brand)
            ->setStorageCapacity('256 Go')
            ->setMemoryRam('8 Go')
            ->setColor('Noir')
            ->setImageName('iphone.jpg')
            ->setImageAlt('iPhone');
        $this->setId($product, 12);

        $products = $this->createMock(ProductRepository::class);
        $products->expects(self::once())
            ->method('findPublished')
            ->with(
                'phones',
                'iphone',
                true,
                'rental',
                'apple',
                '256 Go',
                '8 Go',
                'Noir',
                1050,
                2000,
                true,
                'price_desc',
                48,
                0
            )
            ->willReturn([$product]);
        $products->expects(self::once())
            ->method('countPublished')
            ->with('phones', 'iphone', true, 'rental', 'apple', '256 Go', '8 Go', 'Noir', 1050, 2000, true)
            ->willReturn(49);
        $products->expects(self::once())
            ->method('collectPublishedFacets')
            ->with('phones', 'iphone', true, 'rental', 'apple', '256 Go', '8 Go', 'Noir', 1050, 2000, true)
            ->willReturn(['brands' => ['Apple']]);

        $controller = new ListProductsController(
            new ProductSearchRequestMapper(),
            new ProductCatalogSearchProvider(new ProductQueryService($products), new ArrayAdapter()),
        );

        $request = new Request([
            'page' => '0',
            'perPage' => '100',
            'category' => ' phones ',
            'q' => ' iphone ',
            'homepage' => 'yes',
            'sellingType' => 'RENTAL',
            'brand' => ' apple ',
            'storageCapacity' => '256 Go',
            'memoryRam' => '8 Go',
            'color' => 'Noir',
            'minPrice' => '10.50',
            'maxPrice' => '20',
            'inStock' => 'true',
            'sort' => 'price_desc',
        ]);

        $payload = json_decode((string) $controller($request)->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(1, $payload['data']['meta']['page']);
        self::assertSame(48, $payload['data']['meta']['perPage']);
        self::assertSame(49, $payload['data']['meta']['total']);
        self::assertSame(2, $payload['data']['meta']['totalPages']);
        self::assertSame('Location', $payload['data']['items'][0]['sellingTypeLabel']);
        self::assertSame('Apple', $payload['data']['items'][0]['brand']);
        self::assertSame('/uploads/products/iphone.jpg', $payload['data']['items'][0]['imageUrl']);
        self::assertSame(['brands' => ['Apple']], $payload['data']['facets']);

        $criteria = (new ProductSearchRequestMapper())->map(new Request([
            'page' => '-4',
            'perPage' => '0',
            'category' => '   ',
            'q' => null,
            'homepage' => '0',
            'sellingType' => 'invalid',
            'brand' => 123,
            'storageCapacity' => '',
            'memoryRam' => false,
            'color' => '   ',
            'minPrice' => '-5',
            'maxPrice' => 'oops',
            'inStock' => 0,
            'sort' => 'weird',
        ]));
        self::assertSame(1, $criteria->page);
        self::assertSame(1, $criteria->perPage);
        self::assertNull($criteria->categorySlug);
        self::assertNull($criteria->query);
        self::assertNull($criteria->homepageOnly);
        self::assertNull($criteria->sellingType);
        self::assertNull($criteria->brandSlug);
        self::assertNull($criteria->storageCapacity);
        self::assertNull($criteria->memoryRam);
        self::assertNull($criteria->color);
        self::assertSame(0, $criteria->minPriceCents);
        self::assertNull($criteria->maxPriceCents);
        self::assertFalse($criteria->inStockOnly);
        self::assertNull($criteria->sort);

        $criteria = (new ProductSearchRequestMapper())->map(new Request([
            'minPrice' => '',
            'maxPrice' => '',
        ]));
        self::assertNull($criteria->minPriceCents);
        self::assertNull($criteria->maxPriceCents);
    }

    public function testPaymentMetadataControllerWorkflowAndManagers(): void
    {
        $controller = new ListPaymentMetadataController(new AdminPaymentFormatter());
        $payload = json_decode((string) $controller()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame([
            ['value' => 'open', 'label' => 'Ouvert'],
            ['value' => 'paid', 'label' => 'Payé'],
            ['value' => 'failed', 'label' => 'Échoué'],
            ['value' => 'expired', 'label' => 'Expiré'],
        ], $payload['data']['statuses']);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::exactly(3))->method('persist');
        $entityManager->expects(self::exactly(6))->method('flush');
        $entityManager->expects(self::once())->method('remove');

        $template = new EmailTemplate('Welcome', 'welcome', 'account_created', 'Sujet', '<p>Hi</p>', 'Hi');
        $persistence = new DoctrineUnitOfWork($entityManager);
        (new CreateEmailTemplateHandler($persistence))->create($template);
        (new UpdateEmailTemplateHandler($persistence))->update($template);
        (new DeleteEmailTemplateHandler($persistence))->delete($template);

        $user = $this->user();
        $audit = new AuditRequest('AUD-1', $user, AuditType::SEO, 'https://example.test', 'Goals');
        $logger = new AuditEventLogger(new DoctrineUnitOfWork($entityManager));
        $logger->log($audit, $user, 'created', 'Audit created');
        $logger->save(new \stdClass());

        $order = new Order('ORD-1', $user);
        $workflow = new OrderWorkflowService(new OrderPersistence($entityManager));
        $workflow->cancel($order);
        self::assertSame(Order::STATUS_CANCELLED, $order->getStatus());
        self::assertSame(Order::INVOICE_STATUS_CANCELLED, $order->getInvoiceStatus());
    }

    public function testAdminPaymentFormatterSummariesExposeAttentionState(): void
    {
        $payment = new OrderCheckoutSession('tok', $this->user(), 'cart', 15, 'sess_1', 'https://stripe.test');
        $this->setId($payment, 22);
        $payment
            ->setStatus(OrderCheckoutSession::STATUS_FAILED)
            ->setStripePaymentIntentId('pi_1')
            ->setStripePaymentStatus('requires_action')
            ->setLastStripeEventType('payment_intent.payment_failed')
            ->setFailureCode('card_declined')
            ->setFailureMessage('Declined')
            ->setCustomerFullName('Ada Lovelace')
            ->setTotalPriceCents(12345)
            ->setSubtotalPriceCents(14000)
            ->setDiscountAmountCents(1655)
            ->setItemsPayload([['sku' => 'IP-1']]);

        $formatter = new AdminPaymentFormatter();
        $summary = $formatter->summary($payment);
        self::assertSame('Échoué', $summary['statusLabel']);
        self::assertSame('Action requise', $summary['stripePaymentStatusLabel']);
        self::assertSame('Paiement refusé', $summary['lastStripeEventLabel']);
        self::assertSame('Carte refusée', $summary['failureCodeLabel']);
        self::assertTrue($summary['requiresAttention']);

        $detail = $formatter->detail($payment);
        self::assertSame([['sku' => 'IP-1']], $detail['items']);
        self::assertSame(14000, $detail['subtotalPriceCents']);
        self::assertSame(1655, $detail['discountAmountCents']);
        self::assertSame('Ouverte', $formatter->stripeCheckoutStatusLabel('open'));
        self::assertNull($formatter->stripeCheckoutStatusLabel(''));
        self::assertSame('Terminée', $formatter->stripeCheckoutStatusLabel('complete'));
        self::assertSame('Expirée', $formatter->stripeCheckoutStatusLabel('expired'));
        self::assertSame('unknown', $formatter->stripeCheckoutStatusLabel('unknown'));
        self::assertSame('Ouvert', $formatter->statusLabel(OrderCheckoutSession::STATUS_OPEN));
        self::assertSame('Payé', $formatter->statusLabel(OrderCheckoutSession::STATUS_PAID));
        self::assertSame('Expiré', $formatter->statusLabel(OrderCheckoutSession::STATUS_EXPIRED));
        self::assertSame('custom', $formatter->statusLabel('custom'));
        self::assertSame('Non payé', $formatter->stripePaymentStatusLabel('unpaid'));
        self::assertSame('Aucun paiement requis', $formatter->stripePaymentStatusLabel('no_payment_required'));
        self::assertSame('Moyen de paiement requis', $formatter->stripePaymentStatusLabel('requires_payment_method'));
        self::assertSame('Confirmation requise', $formatter->stripePaymentStatusLabel('requires_confirmation'));
        self::assertSame('En cours de traitement', $formatter->stripePaymentStatusLabel('processing'));
        self::assertSame('Réussi', $formatter->stripePaymentStatusLabel('succeeded'));
        self::assertSame('Annulé', $formatter->stripePaymentStatusLabel('canceled'));
        self::assertSame('other', $formatter->stripePaymentStatusLabel('other'));
        self::assertSame('Session de paiement finalisée', $formatter->stripeEventLabel('checkout.session.completed'));
        self::assertSame('Paiement asynchrone confirmé', $formatter->stripeEventLabel('checkout.session.async_payment_succeeded'));
        self::assertSame('Paiement asynchrone échoué', $formatter->stripeEventLabel('checkout.session.async_payment_failed'));
        self::assertSame('Session de paiement expirée', $formatter->stripeEventLabel('checkout.session.expired'));
        self::assertSame('event.unknown', $formatter->stripeEventLabel('event.unknown'));
        self::assertSame('Fonds insuffisants', $formatter->failureCodeLabel('insufficient_funds'));
        self::assertSame('Carte expirée', $formatter->failureCodeLabel('expired_card'));
        self::assertSame('Code CVC incorrect', $formatter->failureCodeLabel('incorrect_cvc'));
        self::assertSame('Numéro de carte incorrect', $formatter->failureCodeLabel('incorrect_number'));
        self::assertSame('Code postal incorrect', $formatter->failureCodeLabel('incorrect_zip'));
        self::assertSame('Code CVC invalide', $formatter->failureCodeLabel('invalid_cvc'));
        self::assertSame('Mois d’expiration invalide', $formatter->failureCodeLabel('invalid_expiry_month'));
        self::assertSame('Année d’expiration invalide', $formatter->failureCodeLabel('invalid_expiry_year'));
        self::assertSame('Carte déclarée perdue', $formatter->failureCodeLabel('lost_card'));
        self::assertSame('Carte déclarée volée', $formatter->failureCodeLabel('stolen_card'));
        self::assertSame('Erreur de traitement bancaire', $formatter->failureCodeLabel('processing_error'));
        self::assertSame('Authentification bancaire requise', $formatter->failureCodeLabel('authentication_required'));
        self::assertSame('other_code', $formatter->failureCodeLabel('other_code'));
        self::assertSame([
            ['value' => OrderCheckoutSession::STATUS_OPEN, 'label' => 'Ouvert'],
            ['value' => OrderCheckoutSession::STATUS_PAID, 'label' => 'Payé'],
            ['value' => OrderCheckoutSession::STATUS_FAILED, 'label' => 'Échoué'],
            ['value' => OrderCheckoutSession::STATUS_EXPIRED, 'label' => 'Expiré'],
        ], $formatter->statusOptions());
    }

    private function user(): User
    {
        $user = new User('ada@example.com', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $user->setPassword('hashed');

        return $user;
    }

    private function setId(object $entity, int $id): void
    {
        $reflection = new \ReflectionObject($entity);
        $reflection->getProperty('id')->setValue($entity, $id);
    }

    private function coverPrivateConstructor(string $className): void
    {
        $reflection = new \ReflectionClass($className);
        $constructor = $reflection->getConstructor();
        self::assertNotNull($constructor);
        $constructor->setAccessible(true);
        $constructor->invoke($reflection->newInstanceWithoutConstructor());
    }
}
