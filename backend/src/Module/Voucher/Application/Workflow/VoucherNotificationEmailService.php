<?php

declare(strict_types=1);

namespace App\Module\Voucher\Application\Workflow;

use App\Module\Marketing\Application\Port\EmailTemplateRepositoryPort;
use App\Module\Notification\Application\Notification\TemplatedEmailFactory;
use App\Module\Notification\Application\Notification\UserCommunicationNotifier;
use App\Module\User\Domain\Entity\User;
use App\Module\Voucher\Domain\Entity\Voucher;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;

final class VoucherNotificationEmailService
{
    public function __construct(
        private readonly EmailTemplateRepositoryPort $templates,
        private readonly MailerInterface $mailer,
        private readonly UserCommunicationNotifier $userNotifications,
        private readonly LoggerInterface $logger,
        private readonly string $frontendUrl,
        private readonly string $mailerFrom,
        private readonly ?ClockInterface $clock = null,
    ) {
    }

    public function sendCustomerVoucher(User $user, Voucher $voucher): void
    {
        $this->assertVoucherCanBeNotified($user, $voucher);

        $this->userNotifications->notifyInternal(
            $user,
            'voucher:'.$voucher->getId().':customer_offer',
            'Bon de réduction disponible',
            sprintf('Votre bon de réduction %s est disponible sur votre compte.', $voucher->getCode()),
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

        try {
            $email = TemplatedEmailFactory::create(
                $this->mailerFrom,
                'Hociatec',
                $user->getEmail(),
                $user->getFullName(),
                $this->renderTextTemplate($subject, $context),
                $this->renderHtmlTemplate($htmlBody, $context),
                $this->renderTextTemplate($textBody, $context),
            );
            $this->mailer->send($email);
        } catch (\RuntimeException $exception) {
            $this->logger->warning('Voucher notification email send failed.', [
                'userId' => $user->getId(),
                'voucherId' => $voucher->getId(),
                'exception' => $exception,
            ]);

            throw $exception;
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
        $displayTimezone = new \DateTimeZone('Europe/Paris');

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
            'voucher_starts_at' => $voucher->getStartsAt()?->setTimezone($displayTimezone)->format('d/m/Y à H:i') ?? '',
            'voucher_ends_at' => $voucher->getEndsAt()?->setTimezone($displayTimezone)->format('d/m/Y à H:i') ?? '',
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
    private function renderHtmlTemplate(?string $template, array $context): string
    {
        $replacements = [];
        foreach ($context as $key => $value) {
            $replacements['{{'.$key.'}}'] = htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }

        return $this->renderTemplate($template, $replacements);
    }

    /**
     * @param array<string, string> $context
     */
    private function renderTextTemplate(?string $template, array $context): string
    {
        $replacements = [];
        foreach ($context as $key => $value) {
            $replacements['{{'.$key.'}}'] = $value;
        }

        return $this->renderTemplate($template, $replacements);
    }

    /**
     * @param array<string, string> $replacements
     */
    private function renderTemplate(?string $template, array $replacements): string
    {
        $template = (string) $template;
        $rendered = strtr($template, $replacements);
        if (1 === preg_match('/{{\s*[a-zA-Z0-9_]+\s*}}/', $rendered)) {
            throw new \RuntimeException('Le template contient une variable inconnue.');
        }

        return $rendered;
    }

    private function assertVoucherCanBeNotified(User $user, Voucher $voucher): void
    {
        $now = $this->clock?->now() ?? new \DateTimeImmutable();

        if (!$voucher->isActive()) {
            throw new \DomainException('Impossible de notifier un voucher inactif.');
        }

        if (!$voucher->hasStartedAt($now)) {
            throw new \DomainException('Impossible de notifier un voucher qui n\'est pas encore disponible.');
        }

        if ($voucher->isExpiredAt($now)) {
            throw new \DomainException('Impossible de notifier un voucher expiré.');
        }

        if (!$voucher->matchesRecipient($user)) {
            throw new \DomainException('Impossible de notifier un voucher attribué à un autre destinataire.');
        }
    }
}
