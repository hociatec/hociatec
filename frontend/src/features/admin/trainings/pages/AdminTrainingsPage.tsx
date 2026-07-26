import { Link } from 'react-router-dom';

import { useAdminTrainingsOverview } from '../hooks/useAdminTrainingsOverview';
import { PageContainer } from '@/shared/components/PageContainer';
import { AdminListState } from '@/shared/components/admin/AdminDataView';
import { FeedbackMessage, PrimaryLink } from '@/shared/components/ui/page-state';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { AdminTrainingsCatalogSections } from '@/features/admin/trainings/components/AdminTrainingsCatalogSections';

export const AdminTrainingsPage = () => {
  useDocumentTitle('Admin - Formations');

  const {
    trainings,
    categories,
    sessions,
    enrollments,
    loading,
    error,
    message,
    handleDelete,
    handleDeleteSession,
  } = useAdminTrainingsOverview();
  return (
    <PageContainer
      size="admin"
      title="Formations"
      headerActions={
        <div className="flex flex-wrap gap-3">
          <PrimaryLink to="/admin/trainings/new">Nouvelle formation</PrimaryLink>
          <Link to="/admin/trainings/sessions" className="catalog-admin-actions__edit">
            Gérer les sessions
          </Link>
          <Link to="/admin/trainings/sessions/new" className="catalog-admin-actions__edit">
            Nouvelle session
          </Link>
          <Link to="/admin/trainings/enrollments" className="catalog-admin-actions__edit">
            Inscriptions
          </Link>
          <Link to="/admin/trainings/categories" className="catalog-admin-actions__edit">
            Catégories
          </Link>
        </div>
      }
    >
      <div className="mb-6 space-y-1">
        <p className="text-sm text-stone-600">
          {trainings.length} formation{trainings.length > 1 ? 's' : ''}, {sessions.length} session
          {sessions.length > 1 ? 's' : ''}, {enrollments.length} inscription
          {enrollments.length > 1 ? 's' : ''}
        </p>
        <p className="text-sm text-stone-500">
          Vue complète du module formation. La création et la modification restent séparées dans des
          pages dédiées.
        </p>
      </div>

      {error && <FeedbackMessage>{error}</FeedbackMessage>}
      {message && <FeedbackMessage variant="success">{message}</FeedbackMessage>}

      <AdminListState
        loading={loading}
        isEmpty={false}
        loadingLabel="Chargement du module formations..."
        emptyLabel=""
      >
        <AdminTrainingsCatalogSections
          trainings={trainings}
          categories={categories}
          sessions={sessions}
          enrollments={enrollments}
          onDeleteTraining={(training) => void handleDelete(training)}
          onDeleteSession={(session) => void handleDeleteSession(session)}
        />
      </AdminListState>
    </PageContainer>
  );
};
