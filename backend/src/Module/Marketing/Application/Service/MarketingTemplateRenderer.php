<?php

declare(strict_types=1);

namespace App\Module\Marketing\Application\Service;

final class MarketingTemplateRenderer
{
    /** @param array<string, scalar|null> $context */
    public function render(?string $content, array $context, bool $preserveHtml): string
    {
        $pairs = [];
        foreach ($context as $key => $value) {
            $pairs['{{'.$key.'}}'] = $value;
        }

        $rendered = strtr($content ?? '', $pairs);

        return $preserveHtml ? $rendered : trim(html_entity_decode(strip_tags($rendered)));
    }
}
