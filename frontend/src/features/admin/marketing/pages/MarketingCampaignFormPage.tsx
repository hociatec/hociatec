import { Link } from 'react-router-dom';

import { useMarketingCampaignForm } from '@/features/admin/marketing/hooks/useMarketingCampaignForm';
import { PageContainer } from '@/shared/components/PageContainer';
import { FeedbackMessage, LoadingState } from '@/shared/components/ui/page-state';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { MarketingCampaignFormFields } from '@/features/admin/marketing/components/MarketingCampaignFormFields';
import { MarketingCampaignSidebar } from '@/features/admin/marketing/components/MarketingCampaignSidebar';

export const MarketingCampaignFormPage = () => {
  useDocumentTitle('Admin - Nouvelle campagne email');
  const campaign = useMarketingCampaignForm();

  return (
    <PageContainer
      size="admin"
      title="Nouvelle campagne email"
      headerActions={
        <div className="flex flex-wrap gap-3">
          <Link
            to="/admin/marketing"
            className="inline-flex items-center rounded-full border border-brand-200 bg-white px-4 py-2 text-sm font-semibold text-stone-700 transition hover:border-brand-300 hover:text-brand-900"
          >
            Retour aux campagnes
          </Link>
          <Link
            to="/admin/marketing/templates"
            className="inline-flex items-center rounded-full border border-brand-200 bg-white px-4 py-2 text-sm font-semibold text-stone-700 transition hover:border-brand-300 hover:text-brand-900"
          >
            Bibliothèque des templates
          </Link>
        </div>
      }
    >
      {campaign.error && <FeedbackMessage>{campaign.error}</FeedbackMessage>}
      {campaign.message && <FeedbackMessage variant="success">{campaign.message}</FeedbackMessage>}

      {campaign.loading ? (
        <LoadingState>Chargement...</LoadingState>
      ) : (
        <div className="grid gap-8 lg:grid-cols-[minmax(0,1.15fr)_minmax(0,0.85fr)]">
          <form onSubmit={campaign.handleSend} className="register-form-card form-card-grid">
            <MarketingCampaignFormFields campaign={campaign} />

            <div className="flex flex-wrap gap-3">
              <button
                type="button"
                className="catalog-admin-actions__edit"
                onClick={() => void campaign.handlePreview()}
                disabled={campaign.previewLoading}
              >
                {campaign.previewLoading ? 'Prévisualisation...' : 'Prévisualiser l’audience'}
              </button>
              <button type="submit" className="register-form__submit" disabled={campaign.saving}>
                {campaign.saving ? 'Envoi...' : 'Envoyer la campagne'}
              </button>
            </div>
          </form>

          <MarketingCampaignSidebar campaign={campaign} />
        </div>
      )}
    </PageContainer>
  );
};
