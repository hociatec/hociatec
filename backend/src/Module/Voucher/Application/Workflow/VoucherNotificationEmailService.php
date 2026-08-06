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
    private readonly VoucherNotificationValidator $validator;
    private readonly VoucherNotificationContextBuilder $contextBuilder;
    private readonly VoucherNotificationTemplateRenderer $templateRenderer;

    public function __construct(
        private readonly EmailTemplateRepositoryPort $templates,
        private readonly MailerInterface $mailer,
        private readonly UserCommunicationNotifier $userNotifications,
        private readonly LoggerInterface $logger,
        private readonly string $frontendUrl,
        private readonly string $mailerFrom,
        ?ClockInterface $clock = null,
    ) {
        $this->validator = new VoucherNotificationValidator($clock);
        $this->contextBuilder = new VoucherNotificationContextBuilder($frontendUrl);
        $this->templateRenderer = new VoucherNotificationTemplateRenderer();
    }

    public function sendCustomerVoucher(User $user, Voucher $voucher): void
    {
        $this->validator->assertCanNotify($user, $voucher);

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
        $context = $this->contextBuilder->build($user, $voucher);
        $fallback = $this->templateRenderer->fallbackTemplate();

        $subject = $template?->getSubjectTemplate() ?? $fallback['subject'];
        $htmlBody = $template?->getHtmlBody() ?? $fallback['html'];
        $textBody = $template?->getTextBody() ?? $fallback['text'];

        try {
            $email = TemplatedEmailFactory::create(
                $this->mailerFrom,
                'Hociatec',
                $user->getEmail(),
                $user->getFullName(),
                $this->templateRenderer->text($subject, $context),
                $this->templateRenderer->html($htmlBody, $context),
                $this->templateRenderer->text($textBody, $context),
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
}
