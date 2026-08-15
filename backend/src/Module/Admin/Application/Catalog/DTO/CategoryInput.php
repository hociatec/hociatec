<?php

declare(strict_types=1);

namespace App\Module\Admin\Application\Catalog\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class CategoryInput
{
    /**
     * @param list<array{code?:mixed,label?:mixed,inputType?:mixed,helpText?:mixed,options?:mixed,isRequired?:mixed,isGlobalFilter?:mixed}> $attributeDefinitions
     */
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 150)]
        public string $name,
        #[Assert\Length(max: 2000)]
        public ?string $description,
        #[Assert\Length(max: 150)]
        public ?string $slug,
        public bool $isVisible = true,
        public array $attributeDefinitions = [],
    ) {
    }

    /** @param array<string,mixed> $payload */
    public static function fromArray(array $payload): self
    {
        $slug = is_string($payload['slug'] ?? null) ? trim($payload['slug']) : null;

        return new self(
            is_string($payload['name'] ?? null) ? trim($payload['name']) : '',
            is_string($payload['description'] ?? null) ? trim($payload['description']) : null,
            '' === $slug ? null : $slug,
            self::bool($payload['isVisible'] ?? true),
            is_array($payload['attributeDefinitions'] ?? null) ? array_values($payload['attributeDefinitions']) : [],
        );
    }

    private static function bool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        } if (is_string($value)) {
            return in_array(strtolower($value), ['1', 'true', 'on', 'yes'], true);
        }

        return (bool) $value;
    }
}
