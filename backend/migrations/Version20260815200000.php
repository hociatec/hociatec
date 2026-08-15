<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260815200000 extends AbstractMigration
{
    private const PRODUCT_SLUG = 'iphone-16-reconditionne-noir';
    private const SALE_PRICE_CENTS = 69100;
    private const RENTAL_PRICE_CENTS = 6900;

    public function getDescription(): string
    {
        return 'Switch one existing iPhone catalog item from sale to rental for catalog testing';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof MySQLPlatform,
            'Cette migration ne peut être exécutée que sur MySQL.',
        );

        $this->addSql(
            'UPDATE catalog_products
                SET available_for_sale = 0,
                    available_for_rental = 1,
                    sale_price_cents = NULL,
                    rental_price_cents = :priceCents
              WHERE slug = :slug',
            [
                'priceCents' => self::RENTAL_PRICE_CENTS,
                'slug' => self::PRODUCT_SLUG,
            ],
        );
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof MySQLPlatform,
            'Cette migration ne peut être exécutée que sur MySQL.',
        );

        $this->addSql(
            'UPDATE catalog_products
                SET available_for_sale = 1,
                    available_for_rental = 0,
                    sale_price_cents = :priceCents,
                    rental_price_cents = NULL
              WHERE slug = :slug',
            [
                'priceCents' => self::SALE_PRICE_CENTS,
                'slug' => self::PRODUCT_SLUG,
            ],
        );
    }
}
