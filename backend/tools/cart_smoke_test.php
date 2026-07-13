<?php

declare(strict_types=1);

use App\Kernel;
use App\Module\Catalog\Entity\Category;
use App\Module\Catalog\Entity\Product;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\HttpFoundation\Request;

require dirname(__DIR__) . '/vendor/autoload.php';

$projectDir = dirname(__DIR__);
$envFile = $projectDir . '/.env';
if (is_file($envFile)) {
    (new Dotenv())->loadEnv($envFile);
}

$kernel = new Kernel('dev', true);
$kernel->boot();

/** @var \Doctrine\ORM\EntityManagerInterface $entityManager */
$entityManager = $kernel->getContainer()->get('doctrine.orm.entity_manager');
$connection = $entityManager->getConnection();
$schemaManager = method_exists($connection, 'createSchemaManager')
    ? $connection->createSchemaManager()
    : $connection->getSchemaManager();

if (!$schemaManager->tablesExist(['cart_sessions', 'cart_items'])) {
    throw new \RuntimeException('Les tables du module panier sont absentes. Lancez `php bin/console doctrine:migrations:migrate`.');
}

$connection->beginTransaction();
try {
    $uniqueSuffix = bin2hex(random_bytes(4));
    $category = new Category('Panier Test ' . $uniqueSuffix, 'panier-test-' . $uniqueSuffix);
    $entityManager->persist($category);

    $product = new Product(
        'Produit Panier ' . $uniqueSuffix,
        'produit-panier-' . $uniqueSuffix,
        strtoupper('PANIER-' . $uniqueSuffix),
        'Description du produit de test pour le panier.',
        1990,
        5,
        $category,
    );
    $product
        ->setShortDescription('Produit de test pour verifier le panier.')
        ->setIsPublished(true)
        ->setIsFeaturedHome(false)
        ->setImageAlt(null);

    $entityManager->persist($product);
    $entityManager->flush();

    $clientIp = '127.0.0.1';

    $addRequest = Request::create(
        '/api/public/cart/items',
        'POST',
        [],
        [],
        [],
        [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'REMOTE_ADDR' => $clientIp,
        ],
        json_encode([
            'productId' => $product->getId(),
            'quantity' => 2,
        ], JSON_THROW_ON_ERROR),
    );

    $addResponse = $kernel->handle($addRequest);
    $kernel->terminate($addRequest, $addResponse);

    if ($addResponse->getStatusCode() !== 200) {
        throw new \RuntimeException('Echec lors de l\'ajout au panier: ' . $addResponse->getContent());
    }

    $addPayload = json_decode((string) $addResponse->getContent(), true, 512, JSON_THROW_ON_ERROR);
    $cartToken = $addPayload['data']['cart']['token'] ?? null;

    if (!is_string($cartToken) || $cartToken === '') {
        throw new \RuntimeException('Token de panier introuvable dans la reponse.');
    }

    $getRequest = Request::create(
        '/api/public/cart',
        'GET',
        [],
        [],
        [],
        [
            'HTTP_X-Cart-Token' => $cartToken,
            'HTTP_ACCEPT' => 'application/json',
            'REMOTE_ADDR' => $clientIp,
        ],
    );

    $getResponse = $kernel->handle($getRequest);
    $kernel->terminate($getRequest, $getResponse);

    if ($getResponse->getStatusCode() !== 200) {
        throw new \RuntimeException('Echec lors de la recuperation du panier.');
    }

    $removeRequest = Request::create(
        '/api/public/cart/items/' . $product->getId(),
        'DELETE',
        [],
        [],
        [],
        [
            'HTTP_X-Cart-Token' => $cartToken,
            'HTTP_ACCEPT' => 'application/json',
            'REMOTE_ADDR' => $clientIp,
        ],
    );

    $removeResponse = $kernel->handle($removeRequest);
    $kernel->terminate($removeRequest, $removeResponse);

    if ($removeResponse->getStatusCode() !== 200) {
        throw new \RuntimeException('Echec lors du retrait du produit du panier.');
    }

    echo "Smoke test panier OK (token: {$cartToken})." . PHP_EOL;

    $connection->rollBack();
} catch (Throwable $exception) {
    $connection->rollBack();
    $kernel->shutdown();

    fwrite(STDERR, 'Smoke test panier en echec: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}

$kernel->shutdown();
