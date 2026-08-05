import type { RefObject } from 'react';

import type { TradeInDto } from '@/features/tradeIns/publicApi';

type AdminTradeInHeaderProps = {
  selected: TradeInDto;
  onClose: () => void;
  closeButtonRef: RefObject<HTMLButtonElement | null>;
};

export const AdminTradeInHeader = ({
  selected,
  onClose,
  closeButtonRef,
}: AdminTradeInHeaderProps) => (
  <div className="flex items-start justify-between gap-4">
    <div className="flex-1">
      <h2 id="trade-in-dialog-title" className="text-lg font-semibold">
        {selected.reference}
      </h2>
      <p>
        {selected.productName} · {selected.brand ?? 'Marque non renseignée'}
      </p>
      <span className="mt-3 inline-flex rounded-full bg-brand-100 px-3 py-1 text-sm font-semibold text-brand-900">
        {selected.statusLabel}
      </span>
    </div>
    <button
      ref={closeButtonRef}
      type="button"
      className="rounded border border-brand-200 px-3 py-2"
      onClick={onClose}
    >
      Fermer
    </button>
  </div>
);
