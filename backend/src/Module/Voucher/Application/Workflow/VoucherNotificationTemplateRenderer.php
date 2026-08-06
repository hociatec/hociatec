<?php

declare(strict_types=1);

namespace App\Module\Voucher\Application\Workflow;

final readonly class VoucherNotificationTemplateRenderer
{
    /** @return array{subject:string,html:string,text:string} */
    public function fallbackTemplate(): array
    {
        return [
            'subject' => 'Votre bon de réduction {{voucher_code}}',
            'html' => '<p>Bonjour {{first_name}},</p><p>Voici votre bon de réduction <strong>{{voucher_code}}</strong>.</p><p>Valeur: <strong>{{voucher_value_label}}</strong>.</p><p>{{voucher_description}}</p><p>Utilisez-le sur votre prochaine commande depuis <a href="{{cart_url}}">{{cart_url}}</a>.</p>',
            'text' => "Bonjour {{first_name}},\n\nVoici votre bon de réduction {{voucher_code}}.\nValeur: {{voucher_value_label}}.\n{{voucher_description}}\n\nUtilisez-le sur votre prochaine commande: {{cart_url}}",
        ];
    }

    /** @param array<string, string> $context */
    public function html(?string $template, array $context): string
    {
        $replacements = [];
        foreach ($context as $key => $value) {
            $replacements['{{'.$key.'}}'] = htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }

        return $this->render($template, $replacements);
    }

    /** @param array<string, string> $context */
    public function text(?string $template, array $context): string
    {
        $replacements = [];
        foreach ($context as $key => $value) {
            $replacements['{{'.$key.'}}'] = $value;
        }

        return $this->render($template, $replacements);
    }

    /** @param array<string, string> $replacements */
    private function render(?string $template, array $replacements): string
    {
        $rendered = strtr((string) $template, $replacements);
        if (1 === preg_match('/{{\s*[a-zA-Z0-9_]+\s*}}/', $rendered)) {
            throw new \RuntimeException('Le template contient une variable inconnue.');
        }

        return $rendered;
    }
}
