<?php

declare(strict_types=1);

namespace App\Module\TradeIn\Application\Workflow;

use App\Module\TradeIn\Domain\Entity\TradeInRequest;
use App\Module\Voucher\Application\Handler\CreateVoucherHandler;
use App\Module\Voucher\Application\Workflow\VoucherNotificationEmailService;
use App\Module\Voucher\Domain\Entity\Voucher;
use App\Shared\Application\UnitOfWork;
use Psr\Log\LoggerInterface;

final readonly class TradeInStoreCreditVoucherIssuer
{
    public function __construct(
        private CreateVoucherHandler $createVoucher,
        private VoucherNotificationEmailService $voucherNotifications,
        private UnitOfWork $unitOfWork,
        private LoggerInterface $logger,
    ) {
    }

    public function issue(TradeInRequest $request, int $amountCents): Voucher
    {
        if (null === $request->getUser()) {
            throw new \InvalidArgumentException('Un avoir client nécessite un compte Hociatec associé à la demande.');
        }

        $voucher = $this->createVoucher->create([
            'name' => 'Avoir de reprise '.$request->getReference(),
            'code' => 'RPR-'.date('Ymd').'-'.strtoupper(bin2hex(random_bytes(4))),
            'description' => 'Avoir généré après la reprise '.$request->getReference().'.',
            'discountType' => Voucher::TYPE_FIXED_CENTS,
            'discountValue' => $amountCents,
            'isActive' => true,
            'endsAt' => new \DateTimeImmutable('+1 year'),
        ]);
        $voucher->setRecipientUserId($request->getUser()->getId())->setRecipientEmail($request->getEmail());
        $this->unitOfWork->persist($voucher);
        $this->unitOfWork->commit();

        return $voucher;
    }

    public function notifyIssued(TradeInRequest $request, Voucher $voucher): void
    {
        if (null === $request->getUser()) {
            return;
        }

        try {
            $this->voucherNotifications->sendCustomerVoucher($request->getUser(), $voucher);
            $voucher->setSentAt(new \DateTimeImmutable());
            $this->unitOfWork->persist($voucher);
            $this->unitOfWork->commit();
        } catch (\RuntimeException $exception) {
            $this->logger->error('Impossible d’envoyer l’avoir de reprise.', ['reference' => $request->getReference(), 'exception' => $exception]);
        }
    }
}
