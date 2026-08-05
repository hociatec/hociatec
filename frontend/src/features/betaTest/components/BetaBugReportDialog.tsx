import { useState, type FormEvent } from 'react';
import { useMutation, useQueryClient } from '@tanstack/react-query';

import { createBugReport } from '../api/betaApi';
import { BetaBugReportForm } from './BetaBugReportForm';
import { emptyBetaBugReportForm } from './bugReportDialogForm';
import { useToast } from '@/shared/components/ui/toast';
import {
  Dialog,
  DialogBackdrop,
  DialogDescription,
  DialogPanel,
  DialogTitle,
} from '@/shared/components/ui/dialog';
import { adminBetaQueryKeys, betaQueryKeys } from '@/shared/lib/queryKeys';
import { omitUndefinedProperties } from '@/shared/lib/object';

type BetaBugReportDialogProps = {
  open: boolean;
  onClose: () => void;
  campaignId?: number;
  campaignName?: string;
};

export const BetaBugReportDialog = ({ open, onClose, campaignId, campaignName }: BetaBugReportDialogProps) => {
  const queryClient = useQueryClient();
  const toast = useToast();
  const [form, setForm] = useState(emptyBetaBugReportForm);
  const [error, setError] = useState<string | null>(null);

  const resetForm = () => {
    setForm(emptyBetaBugReportForm());
    setError(null);
  };

  const closeDialog = () => {
    resetForm();
    onClose();
  };

  const createMutation = useMutation({
    mutationFn: () => createBugReport(omitUndefinedProperties({ ...form, campaignId })),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: betaQueryKeys.reports() });
      queryClient.invalidateQueries({ queryKey: adminBetaQueryKeys.bugReports() });
      toast.show('Votre signalement a été transmis avec succès.', { variant: 'success' });
      closeDialog();
    },
    onError: (err) => {
      const rawMessage = err instanceof Error ? err.message : 'Impossible d’envoyer le rapport.';
      const isNotFound =
        rawMessage.includes('404')
        || rawMessage.toLowerCase().includes('introuvable')
        || rawMessage.toLowerCase().includes('profil');
      const message = isNotFound
        ? 'Vous devez activer votre profil bêta-testeur pour pouvoir soumettre des rapports.'
        : rawMessage;
      setError(message);
      toast.show(message, { variant: 'error' });
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
        <DialogPanel className="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-xl border border-brand-100 bg-white p-6 shadow-2xl">
          <header className="space-y-2">
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

          <BetaBugReportForm
            error={error}
            form={form}
            isPending={createMutation.isPending}
            onCancel={closeDialog}
            onErrorClear={() => setError(null)}
            onFormChange={setForm}
            onSubmit={handleSubmit}
          />
        </DialogPanel>
      </div>
    </Dialog>
  );
};
