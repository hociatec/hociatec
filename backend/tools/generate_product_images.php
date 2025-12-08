<?php

declare(strict_types=1);

use App\DataFixtures\Catalog\ProductFixtures;

require __DIR__ . '/../vendor/autoload.php';

if (!extension_loaded('gd')) {
    fwrite(STDERR, "The GD extension is required to generate product images." . PHP_EOL);
    exit(1);
}

$reflection = new ReflectionClass(ProductFixtures::class);
$blueprintsConstant = $reflection->getReflectionConstant('PRODUCT_BLUEPRINTS');

if ($blueprintsConstant === false) {
    fwrite(STDERR, 'Unable to read product blueprints from ProductFixtures.' . PHP_EOL);
    exit(1);
}

/** @var array<string, array<int, array{name: string, price: int, image: string}>> $blueprints */
$blueprints = $blueprintsConstant->getValue();

$targetDir = realpath(__DIR__ . '/../public/uploads/products');

if ($targetDir === false) {
    $targetDir = __DIR__ . '/../public/uploads/products';
    if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
        fwrite(STDERR, sprintf('Unable to create directory %s', $targetDir) . PHP_EOL);
        exit(1);
    }
}

foreach ($blueprints as $products) {
    foreach ($products as $product) {
        $filename = $product['image'];
        $path = $targetDir . DIRECTORY_SEPARATOR . $filename;

        if (is_file($path)) {
            continue;
        }

        $width = 800;
        $height = 600;
        $hash = md5($filename);
        $backgroundColor = [
            hexdec(substr($hash, 0, 2)),
            hexdec(substr($hash, 2, 2)),
            hexdec(substr($hash, 4, 2)),
        ];

        $image = imagecreatetruecolor($width, $height);
        $bg = imagecolorallocate($image, ...$backgroundColor);
        imagefilledrectangle($image, 0, 0, $width, $height, $bg);

        $textColor = imagecolorallocate(
            $image,
            255 - $backgroundColor[0],
            255 - $backgroundColor[1],
            255 - $backgroundColor[2]
        );

        $title = strtoupper(substr($product['name'], 0, 20));
        imagestring($image, 5, 24, 24, $title, $textColor);

        $price = number_format((float) $product['price'] / 100, 0, '.', ' ');
        $priceLabel = sprintf('%s EUR', $price);
        imagestring($image, 4, 24, 60, $priceLabel, $textColor);

        imagejpeg($image, $path, 90);
        imagedestroy($image);
    }
}

fwrite(STDOUT, "Generated placeholder product images in {$targetDir}" . PHP_EOL);
