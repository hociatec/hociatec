<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Misc;

use App\Module\Auth\Security\UserChecker;
use App\Module\Catalog\Entity\Category;
use App\Module\Catalog\Entity\Product;
use App\Module\Catalog\Repository\ProductRepository;
use App\Module\Favorite\Controller\RemoveFavoriteController;
use App\Module\Favorite\Service\FavoriteService;
use App\Module\Quote\Controller\Client\DeleteMyQuoteController;
use App\Module\Quote\Entity\Quote;
use App\Module\Quote\Service\QuotePersistence;
use App\Module\Quote\Repository\QuoteRepository;
use App\Module\Quote\Service\QuoteWorkflowService;
use App\Module\User\Controller\Address\DeleteAddressController;
use App\Module\User\Controller\Address\ListMyAddressesController;
use App\Module\User\Controller\Address\SetDefaultAddressController;
use App\Module\User\Entity\ShippingAddress;
use App\Module\User\Entity\User;
use App\Module\User\Repository\ShippingAddressRepository;
use App\Shared\Http\CsrfTokenController;
use App\Shared\Http\CsrfTokenService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;

final class UserControllerAndSecurityBatchTest extends TestCase
{
    public function testUserCheckerAndCsrfTokenFlow(): void
    {
        $checker = new UserChecker();
        $verified = $this->user();
        $verified->setIsVerified(true);
        $checker->checkPreAuth($verified);
        $checker->checkPostAuth($verified);

        $pending = $this->user();
        try {
            $checker->checkPreAuth($pending);
            self::fail('Expected inactive account exception.');
        } catch (CustomUserMessageAccountStatusException $exception) {
            self::assertSame('Votre compte n\'est pas encore activé. Veuillez vérifier vos emails.', $exception->getMessage());
        }

        $service = new CsrfTokenService('dev');
        $request = Request::create('/api/csrf-token', 'GET');
        $controller = new CsrfTokenController($service);
        $response = $controller($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $token = $payload['data']['token'];

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertTrue($service->isValid(Request::create('/api/test', 'POST', server: ['HTTP_X_CSRF_TOKEN' => $token], cookies: [CsrfTokenService::COOKIE_NAME => $token])));
        self::assertFalse($service->isValid(Request::create('/api/test', 'POST')));
    }

    public function testAddressControllersHandleListDeleteAndSetDefault(): void
    {
        $user = $this->user();
        $address = new ShippingAddress($user, 'Ada', 'Lovelace', '1 rue A', '75001', 'Paris', 'FR');
        $this->setId($address, 7);

        $addresses = $this->createMock(ShippingAddressRepository::class);
        $addresses->expects(self::once())->method('findAllForUser')->with($user)->willReturn([$address]);
        $listController = new class($addresses, $user) extends ListMyAddressesController {
            public function __construct(ShippingAddressRepository $addresses, private User $user) { parent::__construct($addresses); }
            public function getUser(): ?User { return $this->user; }
        };
        $listPayload = json_decode((string) $listController()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(7, $listPayload['data']['items'][0]['id']);

        $deleteRepo = $this->createMock(ShippingAddressRepository::class);
        $deleteRepo->expects(self::exactly(2))->method('findOneForUser')->with(7, $user)->willReturnOnConsecutiveCalls(null, $address);
        $deleteRepo->expects(self::once())->method('remove')->with($address, true);
        $deleteController = new class($deleteRepo, $user) extends DeleteAddressController {
            public function __construct(ShippingAddressRepository $addresses, private User $user) { parent::__construct($addresses); }
            public function getUser(): ?User { return $this->user; }
        };
        self::assertSame(Response::HTTP_NOT_FOUND, $deleteController(7)->getStatusCode());
        self::assertSame(Response::HTTP_OK, $deleteController(7)->getStatusCode());

        $defaultRepo = $this->createMock(ShippingAddressRepository::class);
        $defaultRepo->expects(self::exactly(2))->method('findOneForUser')->with(7, $user)->willReturnOnConsecutiveCalls(null, $address);
        $defaultRepo->expects(self::once())->method('setDefault')->with($user, $address);
        $defaultController = new class($defaultRepo, $user) extends SetDefaultAddressController {
            public function __construct(ShippingAddressRepository $addresses, private User $user) { parent::__construct($addresses); }
            public function getUser(): ?User { return $this->user; }
        };
        self::assertSame(Response::HTTP_NOT_FOUND, $defaultController(7)->getStatusCode());
        self::assertSame(Response::HTTP_OK, $defaultController(7)->getStatusCode());
    }

    public function testFavoriteAndDeleteMyQuoteControllers(): void
    {
        $user = $this->user();
        $product = new Product('Phone', 'phone', 'PH-1', 'Desc', 10000, 5, new Category('Phones', 'phones'));
        $this->setId($product, 5);

        $products = $this->createMock(ProductRepository::class);
        $products->expects(self::exactly(2))->method('find')->with(5)->willReturnOnConsecutiveCalls(null, $product);
        $favorites = $this->createMock(FavoriteService::class);
        $favorites->expects(self::once())->method('removeProduct')->with($user, $product);
        $favoriteController = new class($products, $favorites, $user) extends RemoveFavoriteController {
            public function __construct(ProductRepository $products, FavoriteService $favorites, private User $user) { parent::__construct($products, $favorites); }
            public function getUser(): ?User { return $this->user; }
        };
        self::assertSame(Response::HTTP_OK, $favoriteController(5)->getStatusCode());
        self::assertSame(Response::HTTP_OK, $favoriteController(5)->getStatusCode());

        $quote = new Quote('Q-1');
        $quote->setCustomerEmail($user->getEmail());
        $this->setId($quote, 9);
        $quotes = $this->createMock(QuoteRepository::class);
        $quotes->expects(self::exactly(2))->method('find')->with(9)->willReturnOnConsecutiveCalls(null, $quote);
        $entityManager = $this->createMock(\Doctrine\ORM\EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('remove')->with($quote);
        $entityManager->expects(self::once())->method('flush');
        $workflow = new QuoteWorkflowService(new QuotePersistence($entityManager));
        $quoteController = new class($quotes, $workflow, $user) extends DeleteMyQuoteController {
            public function __construct(QuoteRepository $quotes, QuoteWorkflowService $workflow, private User $user) { parent::__construct($quotes, $workflow); }
            public function getUser(): ?User { return $this->user; }
        };
        self::assertSame(Response::HTTP_NOT_FOUND, $quoteController(9)->getStatusCode());
        self::assertSame(Response::HTTP_OK, $quoteController(9)->getStatusCode());
    }

    private function user(): User
    {
        return new User('ada@example.com', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
    }

    private function setId(object $entity, int $id): void
    {
        $reflection = new \ReflectionObject($entity);
        $reflection->getProperty('id')->setValue($entity, $id);
    }
}
