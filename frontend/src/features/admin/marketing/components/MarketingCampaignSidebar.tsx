import { Link } from 'react-router';

import type { useMarketingCampaignForm } from '@/features/admin/marketing/hooks/useMarketingCampaignForm';
import { EmptyState } from '@/shared/components/ui/page-state';
import { PaginationControls } from '@/shared/components/ui/PaginationControls';
import { useAdminPagination } from '@/shared/hooks/useAdminPagination';

type CampaignController = ReturnType<typeof useMarketingCampaignForm>;

export const MarketingCampaignSidebar = ({ campaign }: { campaign: CampaignController }) => {
  const recipientsPagination = useAdminPagination(
    campaign.preview?.recipients ?? [],
    `recipients-${campaign.form.segmentKey}`,
  );
  const advicePagination = useAdminPagination(campaign.audienceAdvice ?? [], `advice-${campaign.form.segmentKey}`);
  const templatesPagination = useAdminPagination(campaign.templatesForSegment, `templates-${campaign.form.segmentKey}`);

  return (
    <div className="space-y-6">
      <div className="register-form-card form-card-grid">
        <h2 className="text-xl font-semibold text-brand-900">Audience</h2>
        <p className="text-sm text-stone-500">{campaign.segments[campaign.form.segmentKey]?.description ?? 'Choisissez une audience.'}</p>
        {campaign.preview ? <>
          <div className="rounded-2xl bg-brand-50 px-4 py-3 text-sm text-stone-700"><strong>{campaign.preview.count}</strong> destinataire(s). {campaign.preview.description}</div>
          <div className="space-y-2">{recipientsPagination.paginatedItems.map((recipient) => <div key={recipient.id} className="rounded-xl border border-brand-100 px-3 py-2 text-sm"><strong>{recipient.fullName}</strong><div className="text-stone-500">{recipient.email}</div></div>)}</div>
          <PaginationControls className="mt-3" page={recipientsPagination.page} total={recipientsPagination.total} totalLabel="destinataire" totalPages={recipientsPagination.totalPages} onPageChange={recipientsPagination.setPage} />
        </> : <p className="text-sm text-stone-500">Lancez une prévisualisation pour voir le volume et quelques exemples.</p>}
      </div>
      <div className="register-form-card form-card-grid">
        <h2 className="text-xl font-semibold text-brand-900">Leviers conseillés</h2>
        <div className="space-y-2 text-sm text-stone-600">{advicePagination.paginatedItems.map((item) => <div key={item} className="rounded-2xl border border-brand-100 bg-brand-50 px-4 py-3">{item}</div>)}</div>
        <PaginationControls className="mt-3" page={advicePagination.page} total={advicePagination.total} totalLabel="levier" totalPages={advicePagination.totalPages} onPageChange={advicePagination.setPage} />
      </div>
      <div className="register-form-card form-card-grid">
        <div className="flex items-center justify-between gap-3"><h2 className="text-xl font-semibold text-brand-900">Templates recommandés</h2><Link to="/admin/marketing/templates" className="text-sm font-semibold text-stone-700 hover:text-brand-900">Voir toute la bibliothèque</Link></div>
        {campaign.templatesForSegment.length === 0 ? <EmptyState>Aucun template actif n’est encore associé à cette audience.</EmptyState> : <div className="space-y-3">{templatesPagination.paginatedItems.map((template) => <div key={template.id} className="rounded-2xl border border-brand-100 px-4 py-4"><div className="flex items-start justify-between gap-3"><div><strong className="block text-brand-900">{template.name}</strong><div className="mt-1 text-sm text-stone-500">{template.subjectTemplate}</div></div><button type="button" className="catalog-admin-actions__edit" onClick={() => campaign.setForm((prev) => ({ ...prev, templateId: String(template.id) }))}>Utiliser</button></div><div className="mt-3 flex flex-wrap gap-3 text-sm"><Link to={`/admin/marketing/templates/${template.id}`} className="font-semibold text-stone-700 hover:text-brand-900">Voir le détail</Link><Link to={`/admin/marketing/templates/${template.id}/edit`} className="font-semibold text-stone-500 hover:text-stone-700">Modifier</Link></div></div>)}</div>}
        <PaginationControls className="mt-3" page={templatesPagination.page} total={templatesPagination.total} totalLabel="template" totalPages={templatesPagination.totalPages} onPageChange={templatesPagination.setPage} />
      </div>
    </div>
  );
};
