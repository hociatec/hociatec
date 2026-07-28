import { useMarketingTemplateForm } from '../hooks/useMarketingTemplateForm';
import { MarketingTemplateEditor } from '../components/MarketingTemplateEditor';
import { PageContainer } from '@/shared/components/layout/PageContainer';
import { FeedbackMessage, LoadingState } from '@/shared/components/ui/page-state';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';

export const MarketingTemplateFormPage = () => {
  const {
    templateId,
    isEdit,
    isTransactionalView,
    form,
    setForm,
    segments,
    initialLoading,
    loading,
    error,
    message,
    handleSubmit,
    navigate,
  } = useMarketingTemplateForm();
  useDocumentTitle(
    isEdit
      ? isTransactionalView
        ? 'Admin - Modifier un e-mail transactionnel'
        : 'Admin - Modifier un modèle d’e-mail'
      : isTransactionalView
        ? 'Admin - Nouvel e-mail transactionnel'
        : 'Admin - Nouveau modèle d’e-mail',
  );

  return (
    <PageContainer
      size="admin"
      title={
        isEdit
          ? isTransactionalView
            ? 'Modifier un e-mail transactionnel'
            : 'Modifier un modèle d’e-mail'
          : isTransactionalView
            ? 'Nouvel e-mail transactionnel'
            : 'Nouveau modèle d’e-mail'
      }
      headerActions={
        <div className="flex flex-wrap gap-3">
          <button
            type="button"
            className="catalog-admin-actions__edit"
            onClick={() =>
              navigate(
                isTransactionalView ? '/admin/transactional-emails' : '/admin/marketing/templates',
              )
            }
          >
            Retour à la liste
          </button>
          {isEdit && templateId ? (
            <button
              type="button"
              className="catalog-admin-actions__edit"
              onClick={() =>
                navigate(
                  isTransactionalView
                    ? `/admin/transactional-emails/${templateId}`
                    : `/admin/marketing/templates/${templateId}`,
                )
              }
            >
              Voir le détail
            </button>
          ) : null}
        </div>
      }
    >
      {error && <FeedbackMessage>{error}</FeedbackMessage>}
      {message && <FeedbackMessage variant="success">{message}</FeedbackMessage>}

      {initialLoading ? (
        <LoadingState>Chargement du modèle...</LoadingState>
      ) : (
        <MarketingTemplateEditor
          form={form}
          setForm={setForm}
          segments={segments}
          loading={loading}
          isEdit={isEdit}
          onSubmit={handleSubmit}
        />
      )}
    </PageContainer>
  );
};
