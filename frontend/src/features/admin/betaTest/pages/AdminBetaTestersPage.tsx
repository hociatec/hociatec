import { useEffect, useState } from 'react';
import {
  fetchAdminBetaTesters,
  exportAdminBetaTesters,
  updateAdminBetaTester,
  deleteAdminBetaTester,
  type AdminBetaTesterDto,
} from '../api';
import { PageContainer } from '@/shared/components/PageContainer';
import { AdminListState, AdminTableShell } from '@/shared/components/admin/AdminDataView';
import { FeedbackMessage } from '@/shared/components/ui/page-state';
import { SearchFilter } from '@/shared/components/filters/SearchFilter';
import { SelectFilter } from '@/shared/components/filters/SelectFilter';
import { FilterBar } from '@/shared/components/filters/FilterBar';
import { useConfirm } from '@/shared/components/ui/confirm';
import {
  Dialog,
  DialogBackdrop,
  DialogPanel,
  DialogTitle,
} from '@/shared/components/ui/dialog';
import {
  betaProfileStatusLabels,
  formatBetaLabel,
  formatBetaList,
  formatDate,
} from '@/features/betaTest/lib/betaLabels';
import { fetchBetaProfileChoices, type BetaProfileChoices } from '@/features/betaTest/api/betaApi';

export const AdminBetaTestersPage = () => {
  const confirm = useConfirm();
  const [testers, setTesters] = useState<AdminBetaTesterDto[]>([]);
  const [search, setSearch] = useState('');
  const [status, setStatus] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);
  const [selectedTester, setSelectedTester] = useState<AdminBetaTesterDto | null>(null);
  const [choices, setChoices] = useState<BetaProfileChoices>({});
  const formatChoiceList = (group: string, values: string[]) => {
    const labels = new Map((choices[group] ?? []).map((choice) => [choice.value, choice.label]));
    const readableValues = values.map((value) => labels.get(value) ?? value);

    return formatBetaList(readableValues);
  };

  const reload = () => {
    const query = `${search ? `&search=${encodeURIComponent(search)}` : ''}${status ? `&status=${status}` : ''}`;
    setLoading(true);
    void fetchAdminBetaTesters(query)
      .then((t) => {
        setTesters(t);
        setError(null);
      })
      .catch((e) =>
        setError(e instanceof Error ? e.message : 'Impossible de charger les données bêta.'),
      )
      .finally(() => setLoading(false));
  };

  useEffect(reload, [search, status]);

  useEffect(() => {
    void fetchBetaProfileChoices().then(setChoices).catch(() => undefined);
  }, []);

  return (
    <PageContainer size="admin" title="Espace bêta">
      <div className="mb-6 flex flex-wrap items-center justify-between gap-3">
        <p className="text-sm text-stone-600">
          {testers.length} profil{testers.length > 1 ? 's' : ''}
        </p>
        <button
          type="button"
          className="rounded border px-4 py-2 text-sm font-semibold hover:bg-stone-50"
          onClick={() => void exportAdminBetaTesters()}
        >
          Exporter les profils CSV
        </button>
      </div>

      {error && <FeedbackMessage>{error}</FeedbackMessage>}

      <FilterBar>
        <SearchFilter
          value={search}
          onChange={setSearch}
          placeholder="Rechercher un nom ou un e-mail"
        />
        <SelectFilter
          value={status}
          onChange={setStatus}
          ariaLabel="Filtrer par statut"
          options={[
            { value: '', label: 'Tous les états' },
            { value: 'pending', label: 'En attente' },
            { value: 'accepted', label: 'Acceptés' },
            { value: 'paused', label: 'En pause' },
            { value: 'rejected', label: 'Refusés' },
          ]}
        />
      </FilterBar>

      <section className="mb-10">
        <h2 className="mb-3 text-xl font-semibold">Bêta-testeurs</h2>
        <AdminListState
          loading={loading}
          isEmpty={testers.length === 0}
          loadingLabel="Chargement…"
          emptyLabel="Aucun bêta-testeur trouvé."
        >
          <AdminTableShell>
            <table className="catalog-admin-table">
              <thead>
                <tr>
                  <th>Candidat</th>
                  <th>Appareils</th>
                  <th>Navigateurs</th>
                  <th>Tests</th>
                  <th>État</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                {testers.map((t) => (
                  <tr key={t.id}>
                    <th>
                      {t.firstName} {t.lastName}
                      <div className="muted">{t.email}</div>
                    </th>
                    <td>{formatChoiceList('devices', t.devices)}</td>
                    <td>{formatChoiceList('browsers', t.browsers)}</td>
                    <td>{formatChoiceList('testingTypes', t.testingTypes)}</td>
                    <td>
                      <select
                        value={t.status}
                        className="rounded border p-1 bg-white"
                        onChange={(e) => void updateAdminBetaTester(t.id, e.target.value).then(reload)}
                      >
                        {Object.entries(betaProfileStatusLabels).map(([value, label]) => (
                          <option key={value} value={value}>{label}</option>
                        ))}
                      </select>
                    </td>
                    <td>
                      <button
                        type="button"
                        className="mr-3 text-sm text-brand-700 underline hover:text-brand-900"
                        onClick={() => setSelectedTester(t)}
                      >
                        Détail
                      </button>
                      <button
                        type="button"
                        className="text-sm text-red-700 underline hover:text-red-900"
                        onClick={async () => {
                          if (await confirm({
                            title: 'Supprimer le profil bêta',
                            description: 'Supprimer définitivement ce profil bêta et ses données ?',
                            confirmLabel: 'Supprimer',
                            cancelLabel: 'Annuler',
                          })) {
                            void deleteAdminBetaTester(t.id).then(reload);
                          }
                        }}
                      >
                        Supprimer
                      </button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </AdminTableShell>
        </AdminListState>
      </section>

      {selectedTester && (
        <Dialog open={Boolean(selectedTester)} onClose={() => setSelectedTester(null)} className="relative z-50">
          <DialogBackdrop className="fixed inset-0 bg-brand-900/70" />
          <div className="fixed inset-0 flex items-center justify-center p-4">
            <DialogPanel className="max-h-[85vh] w-full max-w-3xl overflow-y-auto rounded-xl bg-white p-6 shadow-2xl">
              <div className="mb-4 flex justify-end">
                <button type="button" className="rounded-lg border px-4 py-2 text-sm font-semibold hover:bg-stone-50" onClick={() => setSelectedTester(null)}>
                  Fermer
                </button>
              </div>
              <DialogTitle className="text-xl font-bold text-brand-900">
                Profil bêta de {selectedTester.firstName} {selectedTester.lastName}
              </DialogTitle>
              <p className="mt-1 text-sm text-stone-600"><span className="font-semibold text-stone-900">E-mail : </span>{selectedTester.email}</p>
              <div className="mt-6 grid gap-3 text-sm text-stone-700 md:grid-cols-2">
                <p><span className="font-semibold text-stone-900">État : </span>{formatBetaLabel(selectedTester.status, betaProfileStatusLabels)}</p>
                <p><span className="font-semibold text-stone-900">Créé le : </span>{formatDate(selectedTester.createdAt)}</p>
                <p><span className="font-semibold text-stone-900">Disponibilités : </span>{formatChoiceList('availability', selectedTester.availability)}</p>
                <p><span className="font-semibold text-stone-900">Outils utilisés : </span>{formatChoiceList('assistiveTools', selectedTester.assistiveTools)}</p>
                <p><span className="font-semibold text-stone-900">Matériel : </span>{formatChoiceList('devices', selectedTester.devices)}</p>
                <p><span className="font-semibold text-stone-900">Navigateurs : </span>{formatChoiceList('browsers', selectedTester.browsers)}</p>
                <p><span className="font-semibold text-stone-900">Types de tests : </span>{formatChoiceList('testingTypes', selectedTester.testingTypes)}</p>
              </div>
              <div className="mt-6 space-y-3 text-sm text-stone-700">
                <p className="whitespace-pre-wrap"><span className="font-semibold text-stone-900">Motivation : </span>{selectedTester.motivation || 'Non renseigné'}</p>
                <p><span className="font-semibold text-stone-900">Expérience de test : </span>{formatChoiceList('testingExperience', selectedTester.testingExperience)}</p>
                <p><span className="font-semibold text-stone-900">Capacité à décrire un bug : </span>{formatChoiceList('bugDescriptionAbility', selectedTester.bugDescriptionAbility)}</p>
                <p><span className="font-semibold text-stone-900">Connaissances techniques : </span>{formatChoiceList('technicalKnowledge', selectedTester.technicalKnowledge)}</p>
              </div>
            </DialogPanel>
          </div>
        </Dialog>
      )}
    </PageContainer>
  );
};
