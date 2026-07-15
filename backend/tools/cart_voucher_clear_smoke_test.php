<?php

declare(strict_types=1);

namespace App\Module\Cart\Repository {
    use App\Module\Cart\Entity\CartSession;

    final class CartSessionRepository
    {
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

        public function findOneByUser(mixed $user): ?CartSession
        {
            return null;
        }
    }
}

namespace App\Module\Voucher\Repository {
    final class VoucherRepository
    {
    }
}

namespace {
    require dirname(__DIR__) . '/vendor/autoload.php';

    use App\Module\Cart\Entity\CartSession;
    use App\Module\Cart\Repository\CartSessionRepository;
    use App\Module\Cart\Service\CartService;
    use App\Module\Voucher\Repository\VoucherRepository;
    use App\Module\Voucher\Service\VoucherEngine;
    use Doctrine\ORM\EntityManagerInterface;

    $fakeEntityManagerClass = <<<'PHP'
namespace App\Tests\Support;

final class FakeEntityManager implements \Doctrine\ORM\EntityManagerInterface
{
    public array $persisted = [];
    public array $removed = [];
    public bool $flushed = false;
    public bool $cleared = false;

PHP;

    $renderType = static function (ReflectionNamedType|ReflectionUnionType|ReflectionIntersectionType|null $type): string {
        if ($type === null) {
            return '';
        }

        if ($type instanceof ReflectionNamedType) {
            $name = $type->getName();
            if ($type->allowsNull() && $name !== 'mixed' && $name !== 'null') {
                return '?' . ($type->isBuiltin() ? $name : '\\' . ltrim($name, '\\'));
            }

            return $type->isBuiltin() ? $name : '\\' . ltrim($name, '\\');
        }

        $parts = [];
        foreach ($type->getTypes() as $innerType) {
            $parts[] = $innerType->isBuiltin()
                ? $innerType->getName()
                : '\\' . ltrim($innerType->getName(), '\\');
        }

        return implode('|', $parts);
    };

    $renderParam = static function (ReflectionParameter $parameter) use ($renderType): string {
        $code = '';

        if ($parameter->hasType()) {
            $code .= $renderType($parameter->getType()) . ' ';
        }

        if ($parameter->isPassedByReference()) {
            $code .= '&';
        }

        if ($parameter->isVariadic()) {
            $code .= '...';
        }

        $code .= '$' . $parameter->getName();

        if ($parameter->isOptional() && !$parameter->isVariadic()) {
            if ($parameter->isDefaultValueAvailable()) {
                if ($parameter->isDefaultValueConstant()) {
                    $code .= ' = ' . $parameter->getDefaultValueConstantName();
                } else {
                    $code .= ' = ' . var_export($parameter->getDefaultValue(), true);
                }
            } else {
                $code .= ' = null';
            }
        }

        return $code;
    };

    $interface = new ReflectionClass(EntityManagerInterface::class);
    foreach ($interface->getMethods() as $method) {
        $signature = [];
        foreach ($method->getParameters() as $parameter) {
            $signature[] = $renderParam($parameter);
        }

        $returnType = $method->hasReturnType() ? ': ' . $renderType($method->getReturnType()) : '';
        $methodName = $method->getName();
        $body = match ($methodName) {
            'persist' => '$this->persisted[] = $object;',
            'remove' => '$this->removed[] = $object;',
            'clear' => '$this->cleared = true;',
            'flush' => '$this->flushed = true;',
            'contains' => 'return in_array($object, $this->persisted, true);',
            default => 'throw new \\BadMethodCallException(' . var_export($methodName . ' not implemented in smoke test', true) . ');',
        };

        $fakeEntityManagerClass .= sprintf(
            "\n    public function %s(%s)%s\n    {\n        %s\n    }\n",
            $methodName,
            implode(', ', $signature),
            $returnType,
            $body
        );
    }

    $fakeEntityManagerClass .= "\n}\n";
    eval($fakeEntityManagerClass);

    $repo = new CartSessionRepository();
    $cart = new CartSession('test-cart-token');
    $cart->setVoucherCode('BON-TEST');
    $repo->seed($cart);

    $entityManager = new \App\Tests\Support\FakeEntityManager();
    $voucherEngine = new VoucherEngine(new VoucherRepository());
    $service = new CartService($repo, $entityManager, $voucherEngine);

    $result = $service->clearVoucherCode('test-cart-token');

    if ($result !== $cart) {
        fwrite(STDERR, "Smoke test failed: service returned a different cart instance\n");
        exit(1);
    }

    if ($result->getVoucherCode() !== null) {
        fwrite(STDERR, "Smoke test failed: voucher code was not cleared\n");
        exit(1);
    }

    if (!$entityManager->flushed) {
        fwrite(STDERR, "Smoke test failed: entity manager was not flushed\n");
        exit(1);
    }

    if ($repo->findOneByToken('test-cart-token')?->getVoucherCode() !== null) {
        fwrite(STDERR, "Smoke test failed: repository still exposes a voucher code\n");
        exit(1);
    }

    echo "Smoke test OK: cart voucher removal works.\n";
}
