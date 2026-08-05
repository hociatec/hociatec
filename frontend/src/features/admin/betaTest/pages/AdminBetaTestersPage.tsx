import { exportAdminBetaTesters } from '../api';
import { AdminBetaTesterDetailDialog } from '../components/testers/AdminBetaTesterDetailDialog';
import { AdminBetaTestersTable } from '../components/testers/AdminBetaTestersTable';
import { useAdminBetaTestersPage } from '../hooks/useAdminBetaTestersPage';
import { PageContainer } from '@/shared/components/layout/PageContainer';
import { FeedbackMessage } from '@/shared/components/ui/page-state';
import { SearchFilter } from '@/shared/components/filters/SearchFilter';
import { SelectFilter } from '@/shared/components/filters/SelectFilter';
import { FilterBar } from '@/shared/components/filters/FilterBar';

export const AdminBetaTestersPage = () => {
  const controller = useAdminBetaTestersPage();
  const error =
    controller.testersQuery.error ??
    controller.updateMutation.error ??
    controller.deleteMutation.error;

  return (
    <PageContainer size="admin" title="Espace bêta">
      <div className="mb-6 flex flex-wrap items-center justify-between gap-3">
        <p className="text-sm text-stone-600">
          {controller.testers.length} profil{controller.testers.length > 1 ? 's' : ''}
        </p>
        <button
          type="button"
          className="rounded border px-4 py-2 text-sm font-semibold hover:bg-stone-50"
          onClick={() => void exportAdminBetaTesters()}
        >
          Exporter les profils CSV
        </button>
      </div>

      {error && (
        <FeedbackMessage>
          {error instanceof Error ? error.message : 'Impossible de charger les données bêta.'}
        </FeedbackMessage>
      )}

      <FilterBar>
        <SearchFilter
          value={controller.search}
          onChange={controller.setSearch}
          placeholder="Rechercher un nom ou un e-mail"
        />
        <SelectFilter
          value={controller.status}
          onChange={controller.setStatus}
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

      <AdminBetaTestersTable
        formatChoiceList={controller.formatChoiceList}
        loading={controller.testersQuery.isLoading}
        testers={controller.testers}
        onDelete={(tester) => void controller.deleteTester(tester)}
        onSelect={controller.setSelectedTester}
        onStatusChange={(id, nextStatus) => controller.updateMutation.mutate({ id, nextStatus })}
      />

      <AdminBetaTesterDetailDialog
        formatChoiceList={controller.formatChoiceList}
        tester={controller.selectedTester}
        onClose={() => controller.setSelectedTester(null)}
      />
    </PageContainer>
  );
};
