import { useState, type FormEvent } from 'react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { createBugReport } from '../api/betaApi';
import { useToast } from '@/shared/components/ui/toast';
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
  campaignName?: string;
};

export const BetaBugReportDialog = ({ open, onClose, campaignId, campaignName }: BetaBugReportDialogProps) => {
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
      toast.show('Votre signalement a été transmis avec succès.', { variant: 'success' });
      closeDialog();
    },
    onError: (err) => {
      const rawMsg = err instanceof Error ? err.message : 'Impossible d’envoyer le rapport.';
      const isNotFound =
        rawMsg.includes('404') ||
        rawMsg.toLowerCase().includes('introuvable') ||
        rawMsg.toLowerCase().includes('profil');
      const msg = isNotFound
        ? 'Vous devez activer votre profil bêta-testeur pour pouvoir soumettre des rapports.'
        : rawMsg;
      setError(msg);
      toast.show(msg, { variant: 'error' });
    },
  });

  const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    if (!form.title.trim() || !form.description.trim()) {
      setError('Veuillez renseigner le titre et la description.');
      return;
    }
    createMutation.mutate();
  };

  return (
    <Dialog open={open} onClose={closeDialog} className="relative z-50">
      <DialogBackdrop className="fixed inset-0 bg-brand-900/70" />
      <div className="fixed inset-0 flex items-center justify-center px-4 py-6">
        <DialogPanel className="w-full max-w-lg rounded-xl border border-brand-100 bg-white p-6 shadow-2xl max-h-[90vh] overflow-y-auto">
          <header className="space-y-2">
            <p className="text-xs font-semibold uppercase tracking-[0.2em] text-brand-700">
              Nouveau signalement
            </p>
            <DialogTitle className="text-2xl font-bold text-brand-900">
              Créer un signalement
            </DialogTitle>
            <DialogDescription className="text-sm text-stone-600">
              Renseignez les détails du problème constaté. Le formulaire transmettra votre signalement à l’équipe technique.
            </DialogDescription>
            {campaignName ? (
              <div className="rounded-xl border border-brand-100 bg-brand-50 px-4 py-3 text-sm text-brand-900">
                Campagne liée : <strong>{campaignName}</strong>
              </div>
            ) : (
              <div className="rounded-xl border border-stone-200 bg-stone-50 px-4 py-3 text-sm text-stone-700">
                Signalement général, sans campagne liée.
              </div>
            )}
          </header>

          <form onSubmit={handleSubmit} className="mt-6 space-y-4" aria-busy={createMutation.isPending}>
            <div className="space-y-2">
              <label htmlFor="report-dialog-title" className="block text-sm font-medium text-stone-800">
                Titre du signalement *
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
                placeholder="Ex : erreur au clic sur la validation du panier"
                className="w-full rounded-lg border border-brand-100 px-4 py-3 text-base text-brand-900 shadow-sm outline-none transition focus:border-brand-400 focus:ring-4 focus:ring-brand-100"
                required
                disabled={createMutation.isPending}
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
                onChange={(e) => {
                  setForm({ ...form, description: e.target.value });
                  setError(null);
                }}
                placeholder="Décrivez précisément les étapes pour reproduire le problème..."
                className="w-full rounded-lg border border-brand-100 px-4 py-3 text-base text-brand-900 shadow-sm outline-none transition focus:border-brand-400 focus:ring-4 focus:ring-brand-100"
                required
                disabled={createMutation.isPending}
              />
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div className="space-y-2">
                <label htmlFor="report-dialog-expected" className="block text-sm font-medium text-stone-800">
                  Résultat attendu
                </label>
                <textarea
                  id="report-dialog-expected"
                  rows={2}
                  value={form.expectedBehavior}
                  onChange={(e) => setForm({ ...form, expectedBehavior: e.target.value })}
                  placeholder="Ce qui aurait dû se passer..."
                  className="w-full rounded-lg border border-brand-100 px-4 py-2 text-sm text-brand-900 shadow-sm outline-none transition focus:border-brand-400 focus:ring-4 focus:ring-brand-100"
                  disabled={createMutation.isPending}
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
                  onChange={(e) => setForm({ ...form, actualBehavior: e.target.value })}
                  placeholder="Ce qui s'est produit..."
                  className="w-full rounded-lg border border-brand-100 px-4 py-2 text-sm text-brand-900 shadow-sm outline-none transition focus:border-brand-400 focus:ring-4 focus:ring-brand-100"
                  disabled={createMutation.isPending}
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
                onChange={(e) => setForm({ ...form, severity: e.target.value })}
                className="w-full rounded-lg border border-brand-100 px-4 py-3 text-base text-brand-900 bg-white shadow-sm outline-none transition focus:border-brand-400 focus:ring-4 focus:ring-brand-100"
                disabled={createMutation.isPending}
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
                onChange={(e) =>
                  setForm({ ...form, screenshots: Array.from(e.target.files ?? []).slice(0, 5) })
                }
                className="w-full text-xs text-stone-600 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border file:border-brand-100 file:text-xs file:font-semibold file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100"
                disabled={createMutation.isPending}
              />
            </div>

            {error && (
              <p role="alert" className="text-sm font-medium text-red-700">
                {error}
              </p>
            )}

            <div className="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end mt-6">
              <button
                type="button"
                onClick={closeDialog}
                disabled={createMutation.isPending}
                className="inline-flex items-center justify-center rounded-lg border border-brand-100 px-4 py-3 text-sm font-semibold text-stone-700 transition hover:bg-brand-50 focus:outline-none focus:ring-4 focus:ring-brand-100"
              >
                Annuler
              </button>
              <button
                type="submit"
                disabled={createMutation.isPending}
                className="inline-flex items-center justify-center rounded-lg bg-brand-600 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700 focus:outline-none focus:ring-4 focus:ring-brand-100 disabled:opacity-50"
              >
                {createMutation.isPending ? 'Envoi en cours...' : 'Envoyer le signalement'}
              </button>
            </div>
          </form>
        </DialogPanel>
      </div>
    </Dialog>
  );
};
