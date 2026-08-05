<?php

declare(strict_types=1);

namespace App\Module\TradeIn\Application\Projection;

use App\Module\TradeIn\Domain\Enum\TradeInStatus;

final class TradeInMetadataFormatter
{
    public function __construct(private readonly TradeInFormatter $tradeIns)
    {
    }

    /** @return list<array{value: string, label: string}> */
    public function categories(): array
    {
        return $this->options(['smartphone' => 'Smartphone', 'ordinateur' => 'Ordinateur', 'tablette' => 'Tablette', 'console' => 'Console', 'appareil-photo' => 'Appareil photo', 'audio' => 'Audio', 'electromenager' => 'Électroménager', 'autre' => 'Autre']);
    }

    /** @return list<array{value: string, label: string}> */
    public function conditions(): array
    {
        return $this->options(['comme_neuf' => 'Comme neuf', 'tres_bon' => 'Très bon état', 'bon' => 'Bon état', 'correct' => 'État correct', 'hors_service' => 'Hors service / pour pièces']);
    }

    /** @return list<array{value: string, label: string}> */
    public function statuses(): array
    {
        return array_map(fn (TradeInStatus $status): array => ['value' => $status->value, 'label' => $this->tradeIns->statusLabel($status)], TradeInStatus::cases());
    }

    /** @return list<array{value: string, label: string}> */
    public function paymentMethods(): array
    {
        return $this->options(['bank_transfer' => 'Virement bancaire', 'cash' => 'Espèces', 'store_credit' => 'Avoir client', 'other' => 'Autre']);
    }

    /** @return list<array{value: string, label: string}> */
    public function paymentStatuses(): array
    {
        return $this->options(['pending' => 'Paiement en attente', 'paid' => 'Paiement effectué']);
    }

    /**
     * @param array<string, string> $values
     *
     * @return list<array{value: string, label: string}>
     */
    private function options(array $values): array
    {
        $options = [];
        foreach ($values as $value => $label) {
            $options[] = ['value' => $value, 'label' => $label];
        }

        return $options;
    }
}
