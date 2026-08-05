import type { AdminBetaTesterDto } from '../../api';
import { betaProfileStatusLabels } from '@/features/betaTest/publicApi';
import { AdminListState, AdminTableShell } from '@/shared/components/admin/AdminDataView';

export const AdminBetaTestersTable = ({
  formatChoiceList,
  loading,
  testers,
  onDelete,
  onSelect,
  onStatusChange,
}: {
  formatChoiceList: (group: string, values: string[]) => string;
  loading: boolean;
  testers: AdminBetaTesterDto[];
  onDelete: (tester: AdminBetaTesterDto) => void;
  onSelect: (tester: AdminBetaTesterDto) => void;
  onStatusChange: (id: number, nextStatus: string) => void;
}) => (
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
            {testers.map((tester) => (
              <tr key={tester.id}>
                <th>
                  {tester.firstName} {tester.lastName}
                  <div className="muted">{tester.email}</div>
                </th>
                <td>{formatChoiceList('devices', tester.devices)}</td>
                <td>{formatChoiceList('browsers', tester.browsers)}</td>
                <td>{formatChoiceList('testingTypes', tester.testingTypes)}</td>
                <td>
                  <select
                    value={tester.status}
                    className="rounded border p-1 bg-white"
                    onChange={(event) => onStatusChange(tester.id, event.target.value)}
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
                    onClick={() => onSelect(tester)}
                  >
                    Détail
                  </button>
                  <button
                    type="button"
                    className="text-sm text-red-700 underline hover:text-red-900"
                    onClick={() => onDelete(tester)}
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
);
