<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260815223000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Backfill category attribute definitions from existing catalog product attributes';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof MySQLPlatform,
            'Cette migration ne peut être exécutée que sur MySQL.',
        );

        $this->addSql("UPDATE catalog_categories SET attribute_definitions = '[]'");

        $rows = $this->connection->fetchAllAssociative(
            <<<'SQL'
                SELECT c.id AS category_id, p.attributes
                FROM catalog_categories c
                INNER JOIN catalog_products p ON p.category_id = c.id
                WHERE p.attributes IS NOT NULL
                  AND JSON_LENGTH(p.attributes) > 0
                ORDER BY c.id ASC, p.id ASC
            SQL
        );

        /** @var array<int, array<string, array{code:string,label:string,inputType:string,helpText:null,options:array<string,string>,isRequired:bool,isGlobalFilter:bool,position:int}>> $definitionsByCategory */
        $definitionsByCategory = [];

        foreach ($rows as $row) {
            $categoryId = (int) ($row['category_id'] ?? 0);
            $attributes = json_decode((string) ($row['attributes'] ?? '[]'), true);

            if ($categoryId <= 0 || !is_array($attributes)) {
                continue;
            }

            foreach ($attributes as $index => $attribute) {
                if (!is_array($attribute)) {
                    continue;
                }

                $code = trim((string) ($attribute['code'] ?? ''));
                $label = trim((string) ($attribute['label'] ?? ''));
                $value = trim((string) ($attribute['value'] ?? ''));

                if ('' === $code || '' === $label) {
                    continue;
                }

                if (!isset($definitionsByCategory[$categoryId][$code])) {
                    $definitionsByCategory[$categoryId][$code] = [
                        'code' => $code,
                        'label' => $label,
                        'inputType' => $this->inferInputType($code, $label),
                        'helpText' => null,
                        'options' => [],
                        'isRequired' => false,
                        'isGlobalFilter' => true,
                        'position' => count($definitionsByCategory[$categoryId] ?? []) + $index,
                    ];
                }

                if ('' !== $value) {
                    $definitionsByCategory[$categoryId][$code]['options'][mb_strtolower($value)] = $value;
                }
            }
        }

        foreach ($definitionsByCategory as $categoryId => $definitions) {
            uasort(
                $definitions,
                static fn (array $left, array $right): int => $left['position'] <=> $right['position']
            );

            $payload = array_map(
                static function (array $definition): array {
                    $options = array_values($definition['options']);
                    usort($options, static fn (string $left, string $right): int => strcasecmp($left, $right));

                    return [
                        'code' => $definition['code'],
                        'label' => $definition['label'],
                        'inputType' => $definition['inputType'],
                        'helpText' => $definition['helpText'],
                        'options' => $options,
                        'isRequired' => $definition['isRequired'],
                        'isGlobalFilter' => $definition['isGlobalFilter'],
                    ];
                },
                array_values($definitions),
            );

            $this->addSql(
                'UPDATE catalog_categories SET attribute_definitions = :definitions WHERE id = :id',
                [
                    'definitions' => json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                    'id' => $categoryId,
                ],
                [
                    'definitions' => \PDO::PARAM_STR,
                    'id' => \PDO::PARAM_INT,
                ],
            );
        }
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof MySQLPlatform,
            'Cette migration ne peut être exécutée que sur MySQL.',
        );

        $this->addSql("UPDATE catalog_categories SET attribute_definitions = '[]'");
    }

    private function inferInputType(string $code, string $label): string
    {
        $normalized = mb_strtolower($code.' '.$label);

        if (str_contains($normalized, 'color') || str_contains($normalized, 'couleur')) {
            return 'color';
        }

        return 'select';
    }
}
