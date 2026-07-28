<?php

declare(strict_types=1);

namespace App\Module\Voucher\Service;

use App\Module\Marketing\Repository\EmailTemplateRepository;
use App\Module\User\Entity\User;
use App\Module\Notification\Service\UserCommunicationNotifier;
use App\Module\Voucher\Entity\Voucher;
use App\Shared\Http\OvhRoundcubeMailer;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

final class VoucherNotificationEmailService
{
    public function __construct(
        private readonly EmailTemplateRepository $templates,
        private readonly MailerInterface $mailer,
        private readonly OvhRoundcubeMailer $ovhRoundcubeMailer,
        private readonly UserCommunicationNotifier $userNotifications,
        private readonly string $frontendUrl,
        private readonly string $mailerFrom,
    ) {
    }

    public function sendCustomerVoucher(User $user, Voucher $voucher): void
    {
        $this->userNotifications->notifyInternal(
            $user,
            'voucher:'.$voucher->getId().':customer_offer',
            'Bon de réduction disponible',
            'Votre bon de réduction '.$voucher->getCode().' est disponible sur votre compte.',
            '/vouchers/me',
            'customer_voucher_offer',
        );

        if (!$this->userNotifications->shouldSendEmail($user)) {
            return;
        }

        $template = $this->templates->findActiveOneByScenarioKey('customer_voucher_offer');
        $context = $this->buildContext($user, $voucher);
        $fallback = $this->fallbackTemplate();

        $subject = $template?->getSubjectTemplate() ?? $fallback['subject'];
        $htmlBody = $template?->getHtmlBody() ?? $fallback['html'];
        $textBody = $template?->getTextBody() ?? $fallback['text'];

        $renderedSubject = $this->renderTemplate($subject, $context, false);
        $renderedHtml = $this->renderTemplate($htmlBody, $context, true);
        $renderedText = $this->renderTemplate($textBody, $context, false);

        try {
            $this->ovhRoundcubeMailer->send(
                $user->getEmail(),
                $renderedSubject,
                $renderedText,
            );
        } catch (\Throwable) {
            $email = (new Email())
                ->from(new Address($this->mailerFrom, 'Hociatec'))
                ->to(new Address($user->getEmail(), $user->getFullName()))
                ->subject($renderedSubject)
                ->html($renderedHtml)
                ->text($renderedText);

            $this->mailer->send($email);
        }
    }

    /**
     * @return array<string, string>
     */
    private function buildContext(User $user, Voucher $voucher): array
    {
        $frontendUrl = rtrim($this->frontendUrl, '/');
        $valueLabel = Voucher::TYPE_PERCENT === $voucher->getDiscountType()
            ? $voucher->getDiscountValue().'%'
            : number_format($voucher->getDiscountValue() / 100, 2, ',', ' ').' EUR';

        return [
            'first_name' => $user->getFirstName(),
            'last_name' => $user->getLastName(),
            'full_name' => $user->getFullName(),
            'email' => $user->getEmail(),
            'app_frontend_url' => $frontendUrl,
            'voucher_name' => $voucher->getName(),
            'voucher_code' => $voucher->getCode(),
            'voucher_description' => (string) ($voucher->getDescription() ?? ''),
            'voucher_discount_type' => $voucher->getDiscountType(),
            'voucher_discount_value' => (string) $voucher->getDiscountValue(),
            'voucher_value_label' => $valueLabel,
            'voucher_starts_at' => $voucher->getStartsAt()?->format('d/m/Y H:i') ?? '',
            'voucher_ends_at' => $voucher->getEndsAt()?->format('d/m/Y H:i') ?? '',
            'voucher_is_active' => $voucher->isActive() ? '1' : '0',
            'shop_url' => $frontendUrl.'/boutique',
            'cart_url' => $frontendUrl.'/panier',
        ];
    }

    /**
     * @return array{subject:string,html:string,text:string}
     */
    private function fallbackTemplate(): array
    {
        return [
            'subject' => 'Votre bon de réduction {{voucher_code}}',
            'html' => '<p>Bonjour {{first_name}},</p><p>Voici votre bon de réduction <strong>{{voucher_code}}</strong>.</p><p>Valeur: <strong>{{voucher_value_label}}</strong>.</p><p>{{voucher_description}}</p><p>Utilisez-le sur votre prochaine commande depuis <a href="{{cart_url}}">{{cart_url}}</a>.</p>',
            'text' => "Bonjour {{first_name}},\n\nVoici votre bon de réduction {{voucher_code}}.\nValeur: {{voucher_value_label}}.\n{{voucher_description}}\n\nUtilisez-le sur votre prochaine commande: {{cart_url}}",
        ];
    }

    /**
     * @param array<string, string> $context
     */
    private function renderTemplate(?string $template, array $context, bool $allowHtml): string
    {
        $template = (string) $template;
        $replacements = [];

        foreach ($context as $key => $value) {
            $replacements['{{'.$key.'}}'] = $allowHtml
                ? htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                : $value;
        }

        return strtr($template, $replacements);
    }
}
