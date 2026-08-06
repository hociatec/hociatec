<?php

declare(strict_types=1);

namespace App\Module\Voucher\Application\Workflow;

use App\Module\Marketing\Application\Port\EmailTemplateRepositoryPort;
use App\Module\Notification\Application\Notification\TemplatedEmailFactory;
use App\Module\Notification\Application\Notification\UserCommunicationNotifier;
use App\Module\User\Domain\Entity\User;
use App\Module\Voucher\Domain\Entity\Voucher;
use App\Shared\Application\Mail\EmailSender;
use Psr\Log\LoggerInterface;

final class VoucherNotificationEmailService
{
    public function __construct(
        private readonly EmailTemplateRepositoryPort $templates,
        private readonly EmailSender $mailer,
        private readonly UserCommunicationNotifier $userNotifications,
        private readonly LoggerInterface $logger,
        private readonly string $mailerFrom,
        private readonly VoucherNotificationRendering $rendering,
    ) {
    }

    public function sendCustomerVoucher(User $user, Voucher $voucher): void
    {
        $this->rendering->assertCanNotify($user, $voucher);

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
        $context = $this->rendering->buildContext($user, $voucher);
        $fallback = $this->rendering->fallbackTemplate();

        $subject = $template?->getSubjectTemplate() ?? $fallback['subject'];
        $htmlBody = $template?->getHtmlBody() ?? $fallback['html'];
        $textBody = $template?->getTextBody() ?? $fallback['text'];

        try {
            $email = TemplatedEmailFactory::create(
                $this->mailerFrom,
                'Hociatec',
                $user->getEmail(),
                $user->getFullName(),
                $this->rendering->text($subject, $context),
                $this->rendering->html($htmlBody, $context),
                $this->rendering->text($textBody, $context),
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
