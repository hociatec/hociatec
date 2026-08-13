<?php

declare(strict_types=1);

namespace App\Module\TradeIn\Application\Workflow;

use App\Module\TradeIn\Application\DTO\TradeInClosureInput;
use App\Module\TradeIn\Application\Port\TradeInPrivateFileStoragePort;
use App\Module\TradeIn\Application\Port\TradeInReceiptRenderer;
use App\Module\TradeIn\Domain\Entity\TradeInRequest;
use App\Module\TradeIn\Domain\Enum\TradeInStatus;
use App\Module\Voucher\Domain\Entity\Voucher;
use App\Shared\Application\TransactionManager;
use App\Shared\Application\UnitOfWork;

final readonly class TradeInClosureService
{
    public function __construct(
        private UnitOfWork $persistence,
        private TradeInRequestWorkflow $tradeIns,
        private TransactionManager $transactions,
        private TradeInPrivateFileStoragePort $files,
        private TradeInReceiptRenderer $receiptRenderer,
        private TradeInStoreCreditVoucherIssuer $storeCreditVouchers,
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
        $this->transactions->transactional(function () use ($request, $input, &$voucher): void {
            if ('store_credit' === $input->paymentMethod && null === $request->getUser()) {
                throw new \InvalidArgumentException('Un avoir client nécessite un compte Hociatec associé à la demande.');
            }
            if ('store_credit' === $input->paymentMethod && null === $request->getVoucherCode()) {
                $voucher = $this->storeCreditVouchers->issue($request, $input->finalOfferCents);
                $request->setVoucherCode($voucher->getCode());
            }
            $paymentStatus = 'store_credit' === $input->paymentMethod ? 'paid' : $input->paymentStatus;
            $paidAt = 'paid' === $paymentStatus ? new \DateTimeImmutable() : null;
            $request->setClosure($input->finalOfferCents, $input->paymentMethod, $paymentStatus, $input->transactionReference, $paidAt);
            $request->setAdminNote($input->note);
            $receipt = $this->receiptRenderer->render($this->receiptHtml($request, $input));
            $request->setReceiptPath($this->files->storeReceipt($receipt));
            $this->persistence->persist($request);
            $this->persistence->flush();
        });

        if ($voucher instanceof Voucher && null !== $request->getUser()) {
            $this->storeCreditVouchers->notifyIssued($request, $voucher);
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
}
