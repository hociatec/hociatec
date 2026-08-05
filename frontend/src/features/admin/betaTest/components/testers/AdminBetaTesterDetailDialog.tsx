import type { AdminBetaTesterDto } from '../../api';
import { betaProfileStatusLabels, formatBetaLabel, formatDate } from '@/features/betaTest/publicApi';
import { Dialog, DialogBackdrop, DialogPanel, DialogTitle } from '@/shared/components/ui/dialog';

export const AdminBetaTesterDetailDialog = ({
  formatChoiceList,
  tester,
  onClose,
}: {
  formatChoiceList: (group: string, values: string[]) => string;
  tester: AdminBetaTesterDto | null;
  onClose: () => void;
}) => {
  if (!tester) return null;

  return (
    <Dialog open={Boolean(tester)} onClose={onClose} className="relative z-50">
      <DialogBackdrop className="fixed inset-0 bg-brand-900/70" />
      <div className="fixed inset-0 flex items-center justify-center p-4">
        <DialogPanel className="max-h-[85vh] w-full max-w-3xl overflow-y-auto rounded-xl bg-white p-6 shadow-2xl">
          <div className="mb-4 flex justify-end">
            <button type="button" className="rounded-lg border px-4 py-2 text-sm font-semibold hover:bg-stone-50" onClick={onClose}>
              Fermer
            </button>
          </div>
          <DialogTitle className="text-xl font-bold text-brand-900">
            Profil bêta de {tester.firstName} {tester.lastName}
          </DialogTitle>
          <p className="mt-1 text-sm text-stone-600">
            <span className="font-semibold text-stone-900">E-mail : </span>{tester.email}
          </p>
          <div className="mt-6 grid gap-3 text-sm text-stone-700 md:grid-cols-2">
            <p><span className="font-semibold text-stone-900">État : </span>{formatBetaLabel(tester.status, betaProfileStatusLabels)}</p>
            <p><span className="font-semibold text-stone-900">Créé le : </span>{formatDate(tester.createdAt)}</p>
            <p><span className="font-semibold text-stone-900">Disponibilités : </span>{formatChoiceList('availability', tester.availability)}</p>
            <p><span className="font-semibold text-stone-900">Outils utilisés : </span>{formatChoiceList('assistiveTools', tester.assistiveTools)}</p>
            <p><span className="font-semibold text-stone-900">Matériel : </span>{formatChoiceList('devices', tester.devices)}</p>
            <p><span className="font-semibold text-stone-900">Navigateurs : </span>{formatChoiceList('browsers', tester.browsers)}</p>
            <p><span className="font-semibold text-stone-900">Types de tests : </span>{formatChoiceList('testingTypes', tester.testingTypes)}</p>
          </div>
          <div className="mt-6 space-y-3 text-sm text-stone-700">
            <p className="whitespace-pre-wrap"><span className="font-semibold text-stone-900">Motivation : </span>{tester.motivation || 'Non renseigné'}</p>
            <p><span className="font-semibold text-stone-900">Expérience de test : </span>{formatChoiceList('testingExperience', tester.testingExperience)}</p>
            <p><span className="font-semibold text-stone-900">Capacité à décrire un bug : </span>{formatChoiceList('bugDescriptionAbility', tester.bugDescriptionAbility)}</p>
            <p><span className="font-semibold text-stone-900">Connaissances techniques : </span>{formatChoiceList('technicalKnowledge', tester.technicalKnowledge)}</p>
          </div>
        </DialogPanel>
      </div>
    </Dialog>
  );
};
