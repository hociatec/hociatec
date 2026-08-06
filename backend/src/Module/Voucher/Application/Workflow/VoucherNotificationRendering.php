<?php

declare(strict_types=1);

namespace App\Module\Voucher\Application\Workflow;

use App\Module\User\Domain\Entity\User;
use App\Module\Voucher\Domain\Entity\Voucher;

final readonly class VoucherNotificationRendering
{
    public function __construct(
        private VoucherNotificationValidator $validator,
        private VoucherNotificationContextBuilder $contextBuilder,
        private VoucherNotificationTemplateRenderer $templateRenderer,
    ) {
    }

    public function assertCanNotify(User $user, Voucher $voucher): void
    {
        $this->validator->assertCanNotify($user, $voucher);
    }

    /** @return array<string, string> */
    public function buildContext(User $user, Voucher $voucher): array
    {
        return $this->contextBuilder->build($user, $voucher);
    }

    /** @return array{subject: string, html: string, text: string} */
    public function fallbackTemplate(): array
    {
        return $this->templateRenderer->fallbackTemplate();
    }

    /** @param array<string, string> $context */
    public function text(string $template, array $context): string
    {
        return $this->templateRenderer->text($template, $context);
    }

    /** @param array<string, string> $context */
    public function html(string $template, array $context): string
    {
        return $this->templateRenderer->html($template, $context);
    }
}
