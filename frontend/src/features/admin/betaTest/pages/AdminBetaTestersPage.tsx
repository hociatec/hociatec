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

export const AdminBetaTestersPage = () => {
  const [testers, setTesters] = useState<AdminBetaTesterDto[]>([]);
  const [search, setSearch] = useState('');
  const [status, setStatus] = useState('');
  const [error, setError] = useState<string | null>(null);

  const reload = () => {
    const query = `${search ? `&search=${encodeURIComponent(search)}` : ''}${status ? `&status=${status}` : ''}`;
    void fetchAdminBetaTesters(query)
      .then((t) => {
        setTesters(t);
      })
      .catch((e) =>
        setError(e instanceof Error ? e.message : 'Impossible de charger les données bêta.'),
      );
  };

  useEffect(reload, [search, status]);

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
            { value: '', label: 'Tous les statuts' },
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
          loading={false}
          isEmpty={testers.length === 0}
          loadingLabel="Chargement…"
          emptyLabel="Aucun bêta-testeur trouvé."
        >
          <AdminTableShell>
            <table className="catalog-admin-table">
              <thead>
                <tr>
                  <th>Candidat</th>
                  <th>Accessibilité</th>
                  <th>Appareils</th>
                  <th>Navigateurs</th>
                  <th>Tests</th>
                  <th>Statut</th>
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
                    <td>{t.accessibilityNeed}</td>
                    <td>{t.devices.join(', ')}</td>
                    <td>{t.browsers.join(', ')}</td>
                    <td>{t.testingTypes.join(', ')}</td>
                    <td>
                      <select
                        value={t.status}
                        className="rounded border p-1 bg-white"
                        onChange={(e) => void updateAdminBetaTester(t.id, e.target.value).then(reload)}
                      >
                        <option value="pending">En attente</option>
                        <option value="accepted">Accepté</option>
                        <option value="paused">En pause</option>
                        <option value="rejected">Refusé</option>
                      </select>
                    </td>
                    <td>
                      <button
                        type="button"
                        className="text-sm text-red-700 underline hover:text-red-900"
                        onClick={() => {
                          if (window.confirm('Supprimer ce profil bêta et ses données ?'))
                            void deleteAdminBetaTester(t.id).then(reload);
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
    </PageContainer>
  );
};
