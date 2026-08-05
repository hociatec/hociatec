<?php

declare(strict_types=1);

namespace App\Tests\Unit\Module\Favorite;

use App\Module\Catalog\Domain\Entity\Category;
use App\Module\Catalog\Domain\Entity\Product;
use App\Module\Catalog\Application\Projection\CatalogFormatter;
use App\Module\Catalog\Infrastructure\Repository\ProductRepository;
use App\Module\Favorite\UI\Controller\AddFavoriteController;
use App\Module\Favorite\Domain\Entity\Favorite;
use App\Module\Favorite\Application\Workflow\FavoriteService;
use App\Module\User\Domain\Entity\User;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

final class FavoriteControllersCompletionTest extends TestCase
{
    public function testAddFavoriteControllerCoversNotFoundCreatedAndAlreadyFavorite(): void
    {
        $user = $this->user();
        $product = $this->product();
        $favorite = new Favorite($user, $product);

        $products = $this->getMockBuilder(ProductRepository::class)->disableOriginalConstructor()->getMock();
        $products->method('find')->willReturnMap([[1, null], [2, $product], [3, $product]]);

        $favorites = $this->getMockBuilder(FavoriteService::class)->disableOriginalConstructor()->getMock();
        $favorites->expects(self::exactly(2))->method('addProduct')->with($user, $product)->willReturnOnConsecutiveCalls(
            ['favorite' => $favorite, 'created' => true],
            ['favorite' => $favorite, 'created' => false],
        );

        $controller = new AddFavoriteController($products, $favorites, new CatalogFormatter());
        $controller->setContainer($this->container($user));

        self::assertSame(Response::HTTP_NOT_FOUND, $controller(1)->getStatusCode());
        self::assertSame(Response::HTTP_CREATED, $controller(2)->getStatusCode());

        $already = $controller(3);
        self::assertSame(Response::HTTP_OK, $already->getStatusCode());
        $payload = json_decode((string) $already->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertTrue($payload['data']['alreadyFavorite']);
    }

    private function product(): Product
    {
        return new Product('Laptop', 'laptop', 'SKU-1', 'Desc', 100000, 3, new Category('Computers', 'computers'));
    }

    private function user(): User
    {
        $user = new User('ada@example.com', 'Ada', 'Lovelace', new \DateTimeImmutable('1990-01-01'), '0102030405', 'female');
        $user->setPassword('hashed');

        return $user;
    }

    private function container(User $user): Container
    {
        $tokenStorage = new TokenStorage();
        $tokenStorage->setToken(new UsernamePasswordToken($user, 'main', $user->getRoles()));
        $container = new Container();
        $container->set('security.token_storage', $tokenStorage);

        return $container;
    }
}
