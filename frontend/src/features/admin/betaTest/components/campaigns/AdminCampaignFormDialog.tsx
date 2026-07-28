import { X } from 'lucide-react';

import {
  Dialog,
  DialogBackdrop,
  DialogDescription,
  DialogPanel,
  DialogTitle,
} from '@/shared/components/ui/dialog';
import type { CampaignFormState } from '../../lib/campaignForms';

interface AdminCampaignFormDialogProps {
  description: string;
  form: CampaignFormState;
  isPending: boolean;
  open: boolean;
  pendingLabel: string;
  submitLabel: string;
  title: string;
  onClose: () => void;
  onFormChange: (form: CampaignFormState) => void;
  onSubmit: (event: React.FormEvent) => void;
}

export const AdminCampaignFormDialog = ({
  description,
  form,
  isPending,
  open,
  pendingLabel,
  submitLabel,
  title,
  onClose,
  onFormChange,
  onSubmit,
}: AdminCampaignFormDialogProps) => (
  <Dialog open={open} onClose={onClose} className="relative z-50">
    <DialogBackdrop className="fixed inset-0 bg-brand-900/70" />
    <div className="fixed inset-0 flex items-center justify-center p-4">
      <DialogPanel className="w-full max-w-xl rounded-xl border border-brand-100 bg-white p-6 shadow-2xl">
        <header className="flex items-center justify-between border-b border-stone-200 pb-4">
          <div>
            <p className="text-xs font-semibold uppercase tracking-[0.2em] text-brand-700">
              Campagne bêta
            </p>
            <DialogTitle className="mt-1 text-xl font-bold text-stone-900">
              {title}
            </DialogTitle>
            <DialogDescription className="mt-0.5 text-sm text-stone-500">
              {description}
            </DialogDescription>
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

        <form onSubmit={onSubmit} className="mt-6 space-y-4">
          <div className="space-y-2">
            <label className="block text-sm font-medium text-stone-800">
              Nom de la campagne *
            </label>
            <input
              type="text"
              className="w-full rounded-lg border border-brand-100 px-4 py-3 text-base text-brand-900 shadow-sm outline-none transition focus:border-brand-400 focus:ring-4 focus:ring-brand-100"
              placeholder="Ex: Refonte du panier"
              value={form.name}
              onChange={(event) => onFormChange({ ...form, name: event.target.value })}
              required
            />
          </div>
          <div className="space-y-2">
            <label className="block text-sm font-medium text-stone-800">
              Description *
            </label>
            <textarea
              className="w-full rounded-lg border border-brand-100 px-4 py-3 text-base text-brand-900 shadow-sm outline-none transition focus:border-brand-400 focus:ring-4 focus:ring-brand-100"
              placeholder="Objectifs de la campagne, fonctionnalités à tester..."
              rows={4}
              value={form.description}
              onChange={(event) => onFormChange({ ...form, description: event.target.value })}
              required
            />
          </div>
          <div className="space-y-2">
            <label className="block text-sm font-medium text-stone-800">
              État
            </label>
            <select
              className="w-full rounded-lg border border-brand-100 bg-white px-4 py-3 text-base text-brand-900 shadow-sm outline-none transition focus:border-brand-400 focus:ring-4 focus:ring-brand-100"
              value={form.status}
              onChange={(event) => onFormChange({ ...form, status: event.target.value })}
            >
              <option value="draft">Brouillon</option>
              <option value="active">Active</option>
              <option value="closed">Clôturée</option>
            </select>
          </div>
          <div className="grid gap-4 sm:grid-cols-2">
            <div className="space-y-2">
              <label className="block text-sm font-medium text-stone-800">
                Date de début
              </label>
              <input
                type="date"
                className="w-full rounded-lg border border-brand-100 px-4 py-3 text-base text-brand-900 shadow-sm outline-none transition focus:border-brand-400 focus:ring-4 focus:ring-brand-100"
                value={form.startsAt}
                onChange={(event) => onFormChange({ ...form, startsAt: event.target.value })}
              />
            </div>
            <div className="space-y-2">
              <label className="block text-sm font-medium text-stone-800">
                Date de fin
              </label>
              <input
                type="date"
                className="w-full rounded-lg border border-brand-100 px-4 py-3 text-base text-brand-900 shadow-sm outline-none transition focus:border-brand-400 focus:ring-4 focus:ring-brand-100"
                value={form.endsAt}
                onChange={(event) => onFormChange({ ...form, endsAt: event.target.value })}
              />
            </div>
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
              disabled={isPending}
              className="inline-flex items-center justify-center rounded-lg bg-brand-600 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700 focus:outline-none focus:ring-4 focus:ring-brand-100 disabled:opacity-50"
            >
              {isPending ? pendingLabel : submitLabel}
            </button>
          </div>
        </form>
      </DialogPanel>
    </div>
  </Dialog>
);
