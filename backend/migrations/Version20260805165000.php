<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260805165000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add external product image URLs and seed iPhone catalog visuals.';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE catalog_products ADD image_external_url VARCHAR(2048) DEFAULT NULL');

        $this->seedIphoneImage('iphone-17-pro-max-%', 'https://www.apple.com/newsroom/images/2025/09/apple-unveils-iphone-17-pro-and-iphone-17-pro-max/article/Apple-iPhone-17-Pro-color-lineup-250909_inline.jpg.large.jpg');
        $this->seedIphoneImage('iphone-17-pro-%', 'https://www.apple.com/newsroom/images/2025/09/apple-unveils-iphone-17-pro-and-iphone-17-pro-max/article/Apple-iPhone-17-Pro-color-lineup-250909_inline.jpg.large.jpg');
        $this->seedIphoneImage('iphone-17-%', 'https://www.apple.com/newsroom/images/2025/09/apple-debuts-iphone-17/article/Apple-iPhone-17-color-lineup-250909_big.jpg.large.jpg');

        $this->seedIphoneImage('iphone-16-pro-max-%', 'https://www.apple.com/newsroom/images/2024/09/apple-debuts-iphone-16-pro-and-iphone-16-pro-max/article/Apple-iPhone-16-Pro-finish-lineup-240909_big.jpg.large.jpg');
        $this->seedIphoneImage('iphone-16-pro-%', 'https://www.apple.com/newsroom/images/2024/09/apple-debuts-iphone-16-pro-and-iphone-16-pro-max/article/Apple-iPhone-16-Pro-finish-lineup-240909_big.jpg.large.jpg');
        $this->seedIphoneImage('iphone-16-%', 'https://www.apple.com/newsroom/images/2024/09/apple-introduces-iphone-16-and-iphone-16-plus/article/Apple-iPhone-16-finish-lineup-240909_big.jpg.large.jpg');

        $this->seedIphoneImage('iphone-15-pro-max-%', 'https://www.apple.com/newsroom/images/2023/09/apple-unveils-iphone-15-pro-and-iphone-15-pro-max/article/Apple-iPhone-15-Pro-lineup-color-lineup-230912_big.jpg.large.jpg');
        $this->seedIphoneImage('iphone-15-pro-%', 'https://www.apple.com/newsroom/images/2023/09/apple-unveils-iphone-15-pro-and-iphone-15-pro-max/article/Apple-iPhone-15-Pro-lineup-color-lineup-230912_big.jpg.large.jpg');
        $this->seedIphoneImage('iphone-15-%', 'https://www.apple.com/newsroom/images/2023/09/apple-debuts-iphone-15-and-iphone-15-plus/article/Apple-iPhone-15-lineup-color-lineup-230912_big.jpg.large.jpg');

        $this->seedIphoneImage('iphone-14-pro-max-%', 'https://www.apple.com/newsroom/images/product/iphone/standard/Apple-iPhone-14-Pro-iPhone-14-Pro-Max-space-black-220907_inline.jpg.large.jpg');
        $this->seedIphoneImage('iphone-14-pro-%', 'https://www.apple.com/newsroom/images/product/iphone/standard/Apple-iPhone-14-Pro-iPhone-14-Pro-Max-space-black-220907_inline.jpg.large.jpg');
        $this->seedIphoneImage('iphone-14-%', 'https://www.apple.com/newsroom/images/product/iphone/standard/Apple-iPhone-14-iPhone-14-Plus-2up-blue-220907_inline.jpg.large.jpg');

        $this->seedIphoneImage('iphone-13-pro-max-%', 'https://www.apple.com/newsroom/images/product/iphone/standard/Apple_iPhone-13-Pro_Colors_09142021_big.jpg.large.jpg');
        $this->seedIphoneImage('iphone-13-pro-%', 'https://www.apple.com/newsroom/images/product/iphone/standard/Apple_iPhone-13-Pro_Colors_09142021_big.jpg.large.jpg');
        $this->seedIphoneImage('iphone-13-%', 'https://www.apple.com/newsroom/images/product/iphone/standard/Apple_iphone13_colors_09142021_big.jpg.large.jpg');

        $this->seedIphoneImage('iphone-12-pro-max-%', 'https://www.apple.com/newsroom/images/product/iphone/standard/Apple_announce-iphone12pro_10132020_big.jpg.large.jpg');
        $this->seedIphoneImage('iphone-12-pro-%', 'https://www.apple.com/newsroom/images/product/iphone/standard/Apple_announce-iphone12pro_10132020_big.jpg.large.jpg');
        $this->seedIphoneImage('iphone-12-%', 'https://www.apple.com/newsroom/images/product/iphone/standard/apple_iphone-12_color-blue_10132020_big_carousel.jpg.large.jpg');

        $this->seedIphoneImage('iphone-11-pro-max-%', 'https://www.apple.com/newsroom/images/product/iphone/standard/Apple_iPhone-11-Pro_Colors_091019_big.jpg.large.jpg');
        $this->seedIphoneImage('iphone-11-pro-%', 'https://www.apple.com/newsroom/images/product/iphone/standard/Apple_iPhone-11-Pro_Colors_091019_big.jpg.large.jpg');
        $this->seedIphoneImage('iphone-11-%', 'https://www.apple.com/newsroom/images/product/iphone/standard/Apple_iphone_11-family-lineup-091019_big.jpg.large.jpg');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE catalog_products DROP image_external_url');
    }

    private function seedIphoneImage(string $slugPattern, string $imageUrl): void
    {
        $this->addSql(
            'UPDATE catalog_products SET image_external_url = :image_url WHERE slug LIKE :slug_pattern AND (image_external_url IS NULL OR TRIM(image_external_url) = \'\')',
            [
                'image_url' => $imageUrl,
                'slug_pattern' => $slugPattern,
            ],
        );
    }
}
