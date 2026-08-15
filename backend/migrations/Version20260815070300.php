<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260815070300 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remplace les caractéristiques produit figées par des attributs dynamiques JSON.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE catalog_products ADD attributes JSON NOT NULL DEFAULT ('[]')");
        $this->addSql(<<<'SQL'
            UPDATE catalog_products
            SET attributes = JSON_MERGE_PRESERVE(
                JSON_ARRAY(),
                IF(color IS NOT NULL AND TRIM(color) <> '', JSON_ARRAY(JSON_OBJECT('code', 'color', 'label', 'Couleur', 'value', color)), JSON_ARRAY()),
                IF(storage_capacity IS NOT NULL AND TRIM(storage_capacity) <> '', JSON_ARRAY(JSON_OBJECT('code', 'storage', 'label', 'Stockage', 'value', storage_capacity)), JSON_ARRAY()),
                IF(memory_ram IS NOT NULL AND TRIM(memory_ram) <> '', JSON_ARRAY(JSON_OBJECT('code', 'ram', 'label', 'RAM', 'value', memory_ram)), JSON_ARRAY())
            )
        SQL);
        $this->addSql('ALTER TABLE catalog_products DROP storage_capacity, DROP memory_ram, DROP color');
        $this->addSql('ALTER TABLE catalog_products CHANGE attributes attributes JSON NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE catalog_products ADD storage_capacity VARCHAR(40) DEFAULT NULL, ADD memory_ram VARCHAR(40) DEFAULT NULL, ADD color VARCHAR(60) DEFAULT NULL');
        $this->addSql(<<<'SQL'
            UPDATE catalog_products
            SET
                storage_capacity = JSON_UNQUOTE(JSON_EXTRACT(
                    JSON_UNQUOTE(JSON_SEARCH(attributes, 'one', 'storage', NULL, '$[*].code')),
                    REPLACE(JSON_UNQUOTE(JSON_SEARCH(attributes, 'one', 'storage', NULL, '$[*].code')), '.code', '.value')
                )),
                memory_ram = JSON_UNQUOTE(JSON_EXTRACT(
                    JSON_UNQUOTE(JSON_SEARCH(attributes, 'one', 'ram', NULL, '$[*].code')),
                    REPLACE(JSON_UNQUOTE(JSON_SEARCH(attributes, 'one', 'ram', NULL, '$[*].code')), '.code', '.value')
                )),
                color = JSON_UNQUOTE(JSON_EXTRACT(
                    JSON_UNQUOTE(JSON_SEARCH(attributes, 'one', 'color', NULL, '$[*].code')),
                    REPLACE(JSON_UNQUOTE(JSON_SEARCH(attributes, 'one', 'color', NULL, '$[*].code')), '.code', '.value')
                ))
        SQL);
        $this->addSql('ALTER TABLE catalog_products DROP attributes');
    }
}
