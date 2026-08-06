import { type FormEvent } from 'react';
import { Save, X } from 'lucide-react';

import {
  Dialog,
  DialogBackdrop,
  DialogDescription,
  DialogPanel,
  DialogTitle,
} from '@/shared/components/ui/dialog';
import { formatEuroCents, formatFrenchNumber } from '@/shared/lib/formatters';
import { parseNonNegativeInteger } from '@/shared/lib/parsers';

import type { AdminLoyaltyCustomerDto } from '@/features/loyalty/publicApi';

type LoyaltyBalanceDialogProps = {
  customer: AdminLoyaltyCustomerDto | null;
  draftPoints: string;
  isPending: boolean;
  onClose: () => void;
  onDraftPointsChange: (value: string) => void;
  onSubmit: (event: FormEvent) => void;
  toEuroCents: (points: number) => number;
};

export const LoyaltyBalanceDialog = ({
  customer,
  draftPoints,
  isPending,
  onClose,
  onDraftPointsChange,
  onSubmit,
  toEuroCents,
}: LoyaltyBalanceDialogProps) => {
  const parsedPoints = parseNonNegativeInteger(draftPoints, 0);

  return (
    <Dialog open={customer !== null} onClose={onClose} className="relative z-50">
      <DialogBackdrop className="fixed inset-0 bg-brand-900/70" />
      <div className="fixed inset-0 flex items-center justify-center p-4">
        <DialogPanel className="w-full max-w-lg rounded-xl border border-brand-100 bg-white p-6 shadow-2xl">
          <header className="flex items-center justify-between border-b border-stone-200 pb-4">
            <div>
              <DialogTitle className="text-xl font-bold text-stone-900">
                Ajuster le solde fidélité
              </DialogTitle>
              {customer ? (
                <DialogDescription className="mt-0.5 text-sm text-stone-500">
                  {customer.fullName} · {customer.email}
                </DialogDescription>
              ) : null}
            </div>
            <button
              type="button"
              className="rounded-full p-1 text-stone-400 transition hover:bg-stone-100 hover:text-stone-700"
              onClick={onClose}
              aria-label="Fermer la fenêtre"
            >
              <X size={20} />
            </button>
          </header>

          <form onSubmit={onSubmit} className="mt-6 space-y-5">
            <div className="grid gap-4 sm:grid-cols-2">
              <div className="rounded-lg border border-brand-100 bg-brand-50 px-4 py-3">
                <span className="block text-sm text-stone-500">Solde actuel</span>
                <strong className="mt-1 block text-xl text-brand-900">
                  {formatFrenchNumber(customer?.points ?? 0)} pts
                </strong>
              </div>
              <div className="rounded-lg border border-brand-100 bg-brand-50 px-4 py-3">
                <span className="block text-sm text-stone-500">Valeur actuelle</span>
                <strong className="mt-1 block text-xl text-brand-900">
                  {formatEuroCents(customer?.euroCents ?? 0)}
                </strong>
              </div>
            </div>

            <div className="space-y-2">
              <label htmlFor="loyalty-points" className="block text-sm font-medium text-stone-800">
                Nouveau solde
              </label>
              <input
                id="loyalty-points"
                type="number"
                min={0}
                step={10}
                value={draftPoints}
                onChange={(event) => onDraftPointsChange(event.target.value)}
                className="w-full rounded-lg border border-brand-100 px-4 py-3 text-base text-brand-900 shadow-sm outline-none transition focus:border-brand-400 focus:ring-4 focus:ring-brand-100"
                autoFocus
              />
            </div>

            <div className="rounded-lg border border-stone-200 px-4 py-3 text-sm text-stone-600">
              Équivalent après mise à jour :{' '}
              <strong className="text-brand-900">{formatEuroCents(toEuroCents(parsedPoints))}</strong>
            </div>

            <div className="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
              <button
                type="button"
                onClick={onClose}
                disabled={isPending}
                className="inline-flex items-center justify-center rounded-lg border border-brand-100 px-4 py-3 text-sm font-semibold text-stone-700 transition hover:bg-brand-50 focus:outline-none focus:ring-4 focus:ring-brand-100"
              >
                Annuler
              </button>
              <button
                type="submit"
                disabled={isPending || customer === null}
                className="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-600 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700 focus:outline-none focus:ring-4 focus:ring-brand-100 disabled:opacity-50"
              >
                <Save size={16} aria-hidden="true" />
                {isPending ? 'Enregistrement...' : 'Enregistrer'}
              </button>
            </div>
          </form>
        </DialogPanel>
      </div>
    </Dialog>
  );
};
