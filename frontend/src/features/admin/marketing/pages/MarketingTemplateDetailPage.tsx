import { Link } from 'react-router';

import { useMarketingTemplateDetail } from '../hooks/useMarketingTemplateDetail';
import { PageContainer } from '@/shared/components/layout/PageContainer';
import { AdminMetricCard, AdminMetricGrid } from '@/shared/components/admin/AdminDataView';
import {
  EmptyState,
  FeedbackMessage,
  LoadingState,
  PrimaryLink,
} from '@/shared/components/ui/page-state';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { formatOptionalFrenchDate } from '@/shared/lib/formatters';
import { MarketingTemplateActionsCard } from '@/features/admin/marketing/components/MarketingTemplateActionsCard';
import { MarketingTemplateHtmlPreview } from '@/features/admin/marketing/components/MarketingTemplateHtmlPreview';
import { MarketingTemplateInfoCard } from '@/features/admin/marketing/components/MarketingTemplateInfoCard';

const availableVariables = ['{{first_name}}', '{{last_name}}', '{{full_name}}', '{{email}}', '{{order_count}}', '{{total_spent_eur}}', '{{last_order_number}}', '{{last_order_date}}', '{{days_since_last_order}}', '{{pending_reviews_count}}', '{{app_frontend_url}}', '{{order_number}}', '{{order_status}}', '{{order_status_label}}', '{{order_email_status_title}}', '{{order_payment_instruction}}', '{{order_payment_next_step}}', '{{quote_number}}', '{{order_origin_sentence}}', '{{previous_order_status}}', '{{previous_order_status_label}}', '{{invoice_number}}', '{{invoice_date}}', '{{order_total_eur}}', '{{order_created_at}}', '{{billing_name}}', '{{purchase_order_number}}', '{{order_detail_url}}', '{{orders_list_url}}'];

export const MarketingTemplateDetailPage = () => {
  const { template, segments, loading, error, isTransactionalView } = useMarketingTemplateDetail();
  useDocumentTitle(
    isTransactionalView
      ? 'Admin - Détail d’un e-mail transactionnel'
      : 'Admin - Détail d’un modèle d’e-mail',
  );
  return (
    <PageContainer
      size="admin"
      title={
        template
          ? template.name
          : isTransactionalView
            ? 'Détail d’un e-mail transactionnel'
            : 'Détail d’un modèle d’e-mail'
      }
      headerActions={
        <div className="flex flex-wrap gap-3">
          <Link
            to={isTransactionalView ? '/admin/transactional-emails' : '/admin/marketing/templates'}
            className="inline-flex items-center rounded-full border border-brand-200 bg-white px-4 py-2 text-sm font-semibold text-stone-700 transition hover:border-brand-300 hover:text-brand-900"
          >
            Retour à la bibliothèque
          </Link>
          {template && (
            <>
              {!isTransactionalView && segments[template.scenarioKey]?.type !== 'transactional' ? (
                <Link
                  to={`/admin/marketing/new?templateId=${template.id}`}
                  className="inline-flex items-center rounded-full border border-brand-200 bg-white px-4 py-2 text-sm font-semibold text-stone-700 transition hover:border-brand-300 hover:text-brand-900"
                >
                  Utiliser en campagne
                </Link>
              ) : null}
              <PrimaryLink
                to={
                  isTransactionalView
                    ? `/admin/transactional-emails/${template.id}/edit`
                    : `/admin/marketing/templates/${template.id}/edit`
                }
              >
                Modifier
              </PrimaryLink>
            </>
          )}
        </div>
      }
    >
      {error && <FeedbackMessage>{error}</FeedbackMessage>}

      {loading ? (
        <LoadingState>Chargement du modèle...</LoadingState>
      ) : !template ? (
        <EmptyState>Modèle introuvable.</EmptyState>
      ) : (
        <div className="space-y-8">
          <AdminMetricGrid columns={4}>
            <AdminMetricCard
              label="Scénario"
              value={
                <>
                  {segments[template.scenarioKey]?.label ?? template.scenarioKey}
                  <span className="mt-1 block text-xs font-normal text-stone-500">
                    {segments[template.scenarioKey]?.type === 'transactional'
                      ? 'Transactionnel'
                      : 'Marketing'}
                  </span>
                </>
              }
            />
            <AdminMetricCard label="Statut" value={template.isActive ? 'Actif' : 'Désactivé'} />
            <AdminMetricCard label="Créé le" value={formatOptionalFrenchDate(template.createdAt)} />
            <AdminMetricCard
              label="Mis à jour"
              value={formatOptionalFrenchDate(template.updatedAt)}
            />
          </AdminMetricGrid>

          <div className="grid gap-8 lg:grid-cols-[minmax(0,1fr)_360px]">
            <div className="space-y-6">
              <MarketingTemplateInfoCard
                template={template}
                description={
                  segments[template.scenarioKey]?.description ??
                  'Scénario métier associé au modèle.'
                }
              />

              <MarketingTemplateHtmlPreview name={template.name} htmlBody={template.previewHtmlBody} />

              <div className="rounded-xl border border-brand-100 bg-white p-6 shadow-sm">
                <h2 className="text-xl font-semibold text-brand-900">Version texte</h2>
                <pre className="mt-4 overflow-x-auto whitespace-pre-wrap rounded-2xl border border-brand-100 bg-brand-50 p-5 text-sm text-stone-700">
                  {template.textBody?.trim() || 'Aucune version texte enregistrée.'}
                </pre>
              </div>
            </div>

            <div className="space-y-6">
              <div className="rounded-xl border border-brand-100 bg-white p-6 shadow-sm">
                <h2 className="text-xl font-semibold text-brand-900">Variables disponibles</h2>
                <div className="mt-4 flex flex-wrap gap-2">
                  {availableVariables.map((variable) => (
                    <span
                      key={variable}
                      className="rounded-full border border-brand-100 bg-brand-50 px-3 py-1 text-xs font-semibold text-stone-700"
                    >
                      {variable}
                    </span>
                  ))}
                </div>
              </div>

              <MarketingTemplateActionsCard
                templateId={template.id}
                isTransactionalView={isTransactionalView}
                isMarketingTemplate={segments[template.scenarioKey]?.type !== 'transactional'}
              />
            </div>
          </div>
        </div>
      )}
    </PageContainer>
  );
};
