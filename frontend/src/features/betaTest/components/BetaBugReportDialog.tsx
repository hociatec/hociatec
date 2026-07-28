import { useState, type FormEvent } from 'react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { createBugReport } from '../api/betaApi';
import { useToast } from '@/shared/components/ui/toast';
import { X } from 'lucide-react';
import {
  Dialog,
  DialogBackdrop,
  DialogDescription,
  DialogPanel,
  DialogTitle,
} from '@/shared/components/ui/dialog';

type BetaBugReportDialogProps = {
  open: boolean;
  onClose: () => void;
  campaignId?: number;
};

export const BetaBugReportDialog = ({ open, onClose, campaignId }: BetaBugReportDialogProps) => {
  const queryClient = useQueryClient();
  const toast = useToast();
  const [form, setForm] = useState({
    title: '',
    description: '',
    expectedBehavior: '',
    actualBehavior: '',
    severity: 'normal',
    screenshots: [] as File[],
  });
  const [error, setError] = useState<string | null>(null);

  const resetForm = () => {
    setForm({
      title: '',
      description: '',
      expectedBehavior: '',
      actualBehavior: '',
      severity: 'normal',
      screenshots: [],
    });
    setError(null);
  };

  const closeDialog = () => {
    resetForm();
    onClose();
  };

  const createMutation = useMutation({
    mutationFn: () => createBugReport({ ...form, campaignId }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['betaReports'] });
      queryClient.invalidateQueries({ queryKey: ['adminBugReports'] });
      toast.show('Votre rapport a été soumis avec succès.', { variant: 'success' });
      closeDialog();
    },
    onError: (err) => {
      const msg = err instanceof Error ? err.message : 'Impossible d’envoyer le rapport.';
      setError(msg);
      toast.show(msg, { variant: 'error' });
    },
  });

  const handleSubmit = (event: FormEvent) => {
    event.preventDefault();
    if (!form.title.trim() || !form.description.trim()) {
      setError('Le titre et la description sont obligatoires.');
      return;
    }
    createMutation.mutate();
  };

  return (
    <Dialog open={open} onClose={closeDialog} className="relative z-50">
      <DialogBackdrop className="fixed inset-0 bg-brand-900/70" />
      <div className="fixed inset-0 flex items-center justify-center px-4 py-6 overflow-y-auto">
        <DialogPanel className="w-full max-w-xl rounded-xl border border-brand-100 bg-white p-6 shadow-2xl">
          <header className="flex items-start justify-between border-b border-stone-200 pb-4">
            <div>
              <p className="text-xs font-semibold uppercase tracking-[0.2em] text-brand-700">
                Signalement de bug
              </p>
              <DialogTitle className="text-2xl font-bold text-brand-900 mt-1">
                Créer un nouveau rapport
              </DialogTitle>
              <DialogDescription className="text-sm text-stone-600 mt-1">
                Renseignez les informations relatives au bug constaté. Ce rapport sera transmis à l'équipe technique.
              </DialogDescription>
            </div>
            <button
              type="button"
              onClick={closeDialog}
              className="p-1 text-stone-400 hover:text-stone-700 rounded-full hover:bg-stone-100 transition"
              aria-label="Fermer la fenêtre"
            >
              <X size={20} />
            </button>
          </header>

          <form onSubmit={handleSubmit} className="mt-6 space-y-4" aria-busy={createMutation.isPending}>
            <div>
              <label htmlFor="report-dialog-title" className="block text-sm font-semibold text-stone-800 mb-1">
                Titre du rapport *
              </label>
              <input
                id="report-dialog-title"
                type="text"
                value={form.title}
                onChange={(e) => {
                  setForm({ ...form, title: e.target.value });
                  setError(null);
                }}
                maxLength={180}
                placeholder="Ex: Bouton de validation inactif au paiement"
                className="w-full rounded-lg border border-stone-300 px-4 py-2.5 text-sm focus:border-brand-600 focus:ring-2 focus:ring-brand-100 outline-none"
                required
                disabled={createMutation.isPending}
              />
            </div>

            <div>
              <label htmlFor="report-dialog-description" className="block text-sm font-semibold text-stone-800 mb-1">
                Description détaillée *
              </label>
              <textarea
                id="report-dialog-description"
                rows={4}
                value={form.description}
                onChange={(e) => {
                  setForm({ ...form, description: e.target.value });
                  setError(null);
                }}
                placeholder="Décrivez précisément les étapes pour reproduire le bug..."
                className="w-full rounded-lg border border-stone-300 px-4 py-2.5 text-sm focus:border-brand-600 focus:ring-2 focus:ring-brand-100 outline-none"
                required
                disabled={createMutation.isPending}
              />
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label htmlFor="report-dialog-expected" className="block text-sm font-semibold text-stone-800 mb-1">
                  Résultat attendu
                </label>
                <textarea
                  id="report-dialog-expected"
                  rows={2}
                  value={form.expectedBehavior}
                  onChange={(e) => setForm({ ...form, expectedBehavior: e.target.value })}
                  placeholder="Comportement attendu..."
                  className="w-full rounded-lg border border-stone-300 px-4 py-2 text-sm focus:border-brand-600 focus:ring-2 focus:ring-brand-100 outline-none"
                  disabled={createMutation.isPending}
                />
              </div>

              <div>
                <label htmlFor="report-dialog-actual" className="block text-sm font-semibold text-stone-800 mb-1">
                  Résultat observé
                </label>
                <textarea
                  id="report-dialog-actual"
                  rows={2}
                  value={form.actualBehavior}
                  onChange={(e) => setForm({ ...form, actualBehavior: e.target.value })}
                  placeholder="Comportement réel constaté..."
                  className="w-full rounded-lg border border-stone-300 px-4 py-2 text-sm focus:border-brand-600 focus:ring-2 focus:ring-brand-100 outline-none"
                  disabled={createMutation.isPending}
                />
              </div>
            </div>

            <div>
              <label htmlFor="report-dialog-severity" className="block text-sm font-semibold text-stone-800 mb-1">
                Niveau de gravité
              </label>
              <select
                id="report-dialog-severity"
                value={form.severity}
                onChange={(e) => setForm({ ...form, severity: e.target.value })}
                className="w-full rounded-lg border border-stone-300 px-4 py-2.5 text-sm bg-white focus:border-brand-600 focus:ring-2 focus:ring-brand-100 outline-none"
                disabled={createMutation.isPending}
              >
                <option value="low">Faible (Genant mais pas bloquant)</option>
                <option value="normal">Normale (Fonctionnalité affectée)</option>
                <option value="high">Haute (Fonctionnalité majeure bloquée)</option>
                <option value="critical">Critique (Application plantée / inutilisable)</option>
              </select>
            </div>

            <div>
              <label htmlFor="report-dialog-screenshots" className="block text-sm font-semibold text-stone-800 mb-1">
                Captures d’écran (max. 5 images PNG, JPEG, WebP)
              </label>
              <input
                id="report-dialog-screenshots"
                type="file"
                accept="image/png,image/jpeg,image/webp"
                multiple
                onChange={(e) =>
                  setForm({ ...form, screenshots: Array.from(e.target.files ?? []).slice(0, 5) })
                }
                className="w-full text-xs text-stone-600 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100"
                disabled={createMutation.isPending}
              />
            </div>

            {error && (
              <p className="text-sm text-red-600 font-medium" role="alert">
                {error}
              </p>
            )}

            <div className="pt-4 border-t border-stone-200 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
              <button
                type="button"
                onClick={closeDialog}
                disabled={createMutation.isPending}
                className="inline-flex items-center justify-center rounded-lg border border-stone-300 px-4 py-2.5 text-sm font-semibold text-stone-700 transition hover:bg-stone-50 focus:outline-none focus:ring-4 focus:ring-brand-100"
              >
                Annuler
              </button>
              <button
                type="submit"
                disabled={createMutation.isPending}
                className="inline-flex items-center justify-center rounded-lg bg-brand-700 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-800 focus:outline-none focus:ring-4 focus:ring-brand-100 disabled:opacity-50"
              >
                {createMutation.isPending ? 'Envoi en cours...' : 'Envoyer le rapport'}
              </button>
            </div>
          </form>
        </DialogPanel>
      </div>
    </Dialog>
  );
};
