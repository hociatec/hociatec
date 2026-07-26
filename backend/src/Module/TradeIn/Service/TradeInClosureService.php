<?php

declare(strict_types=1);

namespace App\Module\TradeIn\Service;

use App\Module\Admin\TradeIn\DTO\TradeInClosureInput;
use App\Module\TradeIn\Entity\TradeInRequest;
use App\Module\TradeIn\Enum\TradeInStatus;
use App\Module\Voucher\Entity\Voucher;
use App\Module\Voucher\Service\VoucherManager;
use App\Module\Voucher\Service\VoucherNotificationEmailService;
use App\Shared\Pdf\AccessiblePdfRenderer;
use App\Shared\Persistence\DoctrinePersistence;
use Psr\Log\LoggerInterface;

final readonly class TradeInClosureService
{
    public function __construct(
        private TradeInPersistence $persistence,
        private TradeInService $tradeIns,
        private DoctrinePersistence $doctrine,
        private TradeInPrivateFileStorage $files,
        private AccessiblePdfRenderer $pdf,
        private VoucherManager $vouchers,
        private VoucherNotificationEmailService $voucherNotifications,
        private LoggerInterface $logger,
    ) {
    }

    public function close(TradeInRequest $request, TradeInClosureInput $input): void
    {
        if (!in_array($request->getStatus(), [TradeInStatus::INSPECTED, TradeInStatus::COMPLETED], true)) {
            throw new \InvalidArgumentException('La reprise doit être inspectée avant sa clôture.');
        }
        if (0 >= $input->finalOfferCents) {
            throw new \InvalidArgumentException('Le montant final doit être supérieur à zéro.');
        }

        $voucher = null;
        $this->doctrine->transactional(function () use ($request, $input, &$voucher): void {
            if ('store_credit' === $input->paymentMethod && null === $request->getUser()) {
                throw new \InvalidArgumentException('Un avoir client nécessite un compte Hociatec associé à la demande.');
            }
            if ('store_credit' === $input->paymentMethod && null === $request->getVoucherCode()) {
                $voucher = $this->vouchers->create([
                    'name' => 'Avoir de reprise '.$request->getReference(),
                    'code' => 'RPR-'.date('Ymd').'-'.strtoupper(bin2hex(random_bytes(4))),
                    'description' => 'Avoir généré après la reprise '.$request->getReference().'.',
                    'discountType' => Voucher::TYPE_FIXED_CENTS,
                    'discountValue' => $input->finalOfferCents,
                    'isActive' => true,
                    'endsAt' => new \DateTimeImmutable('+1 year'),
                ]);
                $voucher->setRecipientUserId($request->getUser()?->getId())->setRecipientEmail($request->getEmail());
                $this->doctrine->persist($voucher);
                $this->doctrine->flush();
                $request->setVoucherCode($voucher->getCode());
            }
            $paymentStatus = 'store_credit' === $input->paymentMethod ? 'paid' : $input->paymentStatus;
            $paidAt = 'paid' === $paymentStatus ? new \DateTimeImmutable() : null;
            $request->setClosure($input->finalOfferCents, $input->paymentMethod, $paymentStatus, $input->transactionReference, $paidAt);
            $request->setAdminNote($input->note);
            $receipt = $this->renderReceipt($this->receiptHtml($request, $input));
            $request->setReceiptPath($this->files->storeReceipt($receipt));
            $this->persistence->save($request);
        });

        if ($voucher instanceof Voucher && null !== $request->getUser()) {
            try {
                $this->voucherNotifications->sendCustomerVoucher($request->getUser(), $voucher);
                $voucher->setSentAt(new \DateTimeImmutable());
                $this->doctrine->persist($voucher);
                $this->doctrine->flush();
            } catch (\Throwable $exception) {
                $this->logger->error('Impossible d’envoyer l’avoir de reprise.', ['reference' => $request->getReference(), 'exception' => $exception]);
            }
        }

        if (TradeInStatus::COMPLETED !== $request->getStatus()) {
            $this->tradeIns->setStatus($request, TradeInStatus::COMPLETED);
        }
    }

    private function receiptHtml(TradeInRequest $request, TradeInClosureInput $input): string
    {
        $escape = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $amount = number_format($input->finalOfferCents / 100, 2, ',', ' ').' €';

        $voucherBlock = null !== $request->getVoucherCode() ? '<dt>Code avoir</dt><dd>'.$escape($request->getVoucherCode()).'</dd>' : '';

        return sprintf(
            '<!doctype html><html lang="fr"><head><meta charset="utf-8"><title>Justificatif de reprise %s</title><style>body{font-family:DejaVu Sans,Arial,sans-serif;color:#172033;line-height:1.5}h1{color:#5b21b6}dl{display:grid;grid-template-columns:180px 1fr;gap:8px}dt{font-weight:bold}dd{margin:0}</style></head><body><main><h1>Justificatif de reprise</h1><p>Référence : <strong>%s</strong></p><dl><dt>Demandeur</dt><dd>%s %s</dd><dt>Matériel</dt><dd>%s</dd><dt>Montant final</dt><dd>%s</dd><dt>Mode de règlement</dt><dd>%s</dd><dt>Statut du paiement</dt><dd>%s</dd><dt>Référence de transaction</dt><dd>%s</dd>%s</dl><p>Document généré par Hociatec le %s.</p></main></body></html>',
            $escape($request->getReference()),
            $escape($request->getReference()),
            $escape($request->getFirstName()),
            $escape($request->getLastName()),
            $escape($request->getProductName()),
            $escape($amount),
            $escape(match ($input->paymentMethod) {
                'bank_transfer' => 'Virement bancaire',
                'cash' => 'Espèces',
                'store_credit' => 'Avoir client',
                default => 'Autre',
            }),
            $escape('paid' === $input->paymentStatus ? 'Payé' : 'En attente'),
            $escape($input->transactionReference ?? 'Non renseignée'),
            $voucherBlock,
            (new \DateTimeImmutable())->format('d/m/Y H:i'),
        );
    }

    private function renderReceipt(string $html): string
    {
        try {
            return $this->pdf->render($html, 'trade-in-receipt', 'Le justificatif de reprise n’a pas pu être généré.');
        } catch (\RuntimeException $exception) {
            if (!class_exists(\Dompdf\Dompdf::class)) {
                throw $exception;
            }

            $dompdf = new \Dompdf\Dompdf(['isRemoteEnabled' => false]);
            $dompdf->loadHtml($html, 'UTF-8');
            $dompdf->setPaper('A4');
            $dompdf->render();

            return $dompdf->output();
        }
    }
}
