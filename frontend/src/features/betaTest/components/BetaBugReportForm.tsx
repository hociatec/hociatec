import type { FormEvent } from 'react';

import type { BetaBugReportFormState } from './bugReportDialogForm';

interface BetaBugReportFormProps {
  error: string | null;
  form: BetaBugReportFormState;
  isPending: boolean;
  onCancel: () => void;
  onErrorClear: () => void;
  onFormChange: (form: BetaBugReportFormState) => void;
  onSubmit: (event: FormEvent<HTMLFormElement>) => void;
}

export const BetaBugReportForm = ({
  error,
  form,
  isPending,
  onCancel,
  onErrorClear,
  onFormChange,
  onSubmit,
}: BetaBugReportFormProps) => (
  <form onSubmit={onSubmit} className="mt-6 space-y-4" aria-busy={isPending}>
    <div className="space-y-2">
      <label htmlFor="report-dialog-title" className="block text-sm font-medium text-stone-800">
        Titre du signalement *
      </label>
      <input
        id="report-dialog-title"
        type="text"
        value={form.title}
        onChange={(event) => {
          onFormChange({ ...form, title: event.target.value });
          onErrorClear();
        }}
        maxLength={180}
        placeholder="Ex : erreur au clic sur la validation du panier"
        className="w-full rounded-lg border border-brand-100 px-4 py-3 text-base text-brand-900 shadow-sm outline-none transition focus:border-brand-400 focus:ring-4 focus:ring-brand-100"
        required
        disabled={isPending}
      />
    </div>

    <div className="space-y-2">
      <label htmlFor="report-dialog-description" className="block text-sm font-medium text-stone-800">
        Description détaillée *
      </label>
      <textarea
        id="report-dialog-description"
        rows={3}
        value={form.description}
        onChange={(event) => {
          onFormChange({ ...form, description: event.target.value });
          onErrorClear();
        }}
        placeholder="Décrivez précisément les étapes pour reproduire le problème..."
        className="w-full rounded-lg border border-brand-100 px-4 py-3 text-base text-brand-900 shadow-sm outline-none transition focus:border-brand-400 focus:ring-4 focus:ring-brand-100"
        required
        disabled={isPending}
      />
    </div>

    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
      <div className="space-y-2">
        <label htmlFor="report-dialog-expected" className="block text-sm font-medium text-stone-800">
          Résultat attendu
        </label>
        <textarea
          id="report-dialog-expected"
          rows={2}
          value={form.expectedBehavior}
          onChange={(event) => onFormChange({ ...form, expectedBehavior: event.target.value })}
          placeholder="Ce qui aurait dû se passer..."
          className="w-full rounded-lg border border-brand-100 px-4 py-2 text-sm text-brand-900 shadow-sm outline-none transition focus:border-brand-400 focus:ring-4 focus:ring-brand-100"
          disabled={isPending}
        />
      </div>

      <div className="space-y-2">
        <label htmlFor="report-dialog-actual" className="block text-sm font-medium text-stone-800">
          Résultat constaté
        </label>
        <textarea
          id="report-dialog-actual"
          rows={2}
          value={form.actualBehavior}
          onChange={(event) => onFormChange({ ...form, actualBehavior: event.target.value })}
          placeholder="Ce qui s'est produit..."
          className="w-full rounded-lg border border-brand-100 px-4 py-2 text-sm text-brand-900 shadow-sm outline-none transition focus:border-brand-400 focus:ring-4 focus:ring-brand-100"
          disabled={isPending}
        />
      </div>
    </div>

    <div className="space-y-2">
      <label htmlFor="report-dialog-severity" className="block text-sm font-medium text-stone-800">
        Niveau de gravité
      </label>
      <select
        id="report-dialog-severity"
        value={form.severity}
        onChange={(event) => onFormChange({ ...form, severity: event.target.value })}
        className="w-full rounded-lg border border-brand-100 bg-white px-4 py-3 text-base text-brand-900 shadow-sm outline-none transition focus:border-brand-400 focus:ring-4 focus:ring-brand-100"
        disabled={isPending}
      >
        <option value="low">Faible - Problème mineur</option>
        <option value="normal">Normale - Gêne modérée</option>
        <option value="high">Haute - Fonctionnalité bloquée</option>
        <option value="critical">Critique - Application bloquée</option>
      </select>
    </div>

    <div className="space-y-2">
      <label htmlFor="report-dialog-screenshots" className="block text-sm font-medium text-stone-800">
        Captures d’écran (max 5)
      </label>
      <input
        id="report-dialog-screenshots"
        type="file"
        accept="image/png,image/jpeg,image/webp"
        multiple
        onChange={(event) =>
          onFormChange({ ...form, screenshots: Array.from(event.target.files ?? []).slice(0, 5) })
        }
        className="w-full text-xs text-stone-600 file:mr-3 file:rounded-lg file:border file:border-brand-100 file:bg-brand-50 file:px-4 file:py-2 file:text-xs file:font-semibold file:text-brand-700 hover:file:bg-brand-100"
        disabled={isPending}
      />
    </div>

    {error && (
      <p role="alert" className="text-sm font-medium text-red-700">
        {error}
      </p>
    )}

    <div className="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
      <button
        type="button"
        onClick={onCancel}
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
        {isPending ? 'Envoi en cours...' : 'Envoyer le signalement'}
      </button>
    </div>
  </form>
);
