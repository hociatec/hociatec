<?php

declare(strict_types=1);

namespace App\Module\Order\Application\Provider;

final class OrderNotificationTemplateRenderer
{
    /** @param array<string, scalar|null> $context */
    public function render(?string $template, array $context, bool $allowHtml): string
    {
        $replacements = [];
        foreach ($context as $key => $value) {
            $renderedValue = (string) ($value ?? '');
            $replacements['{{'.$key.'}}'] = $allowHtml
                ? htmlspecialchars($renderedValue, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                : $renderedValue;
        }

        return strtr((string) $template, $replacements);
    }
}
