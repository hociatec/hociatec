import { Link } from 'react-router-dom';

import { useMarketingCampaignForm } from '@/features/admin/marketing/hooks/useMarketingCampaignForm';
import { PageContainer } from '@/shared/components/PageContainer';
import { EmptyState, FeedbackMessage, LoadingState } from '@/shared/components/ui/page-state';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';

export const MarketingCampaignFormPage = () => {
  useDocumentTitle('Admin - Nouvelle campagne email');
  const campaign = useMarketingCampaignForm();

  return (
    <PageContainer size="admin"
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
            <label className="register-form__field">
              <span className="register-form__label">Nom de campagne</span>
              <input className="register-form__input" value={campaign.form.name} onChange={(event) => campaign.setForm((prev) => ({ ...prev, name: event.target.value }))} />
            </label>

            <div className="grid gap-4 md:grid-cols-2">
              <label className="register-form__field">
                <span className="register-form__label">Template</span>
                <select className="register-form__input" value={campaign.form.templateId} onChange={(event) => campaign.setForm((prev) => ({ ...prev, templateId: event.target.value }))}>
                  <option value="">Sans template</option>
                  {campaign.activeTemplates.map((template) => (
                    <option key={template.id} value={template.id}>
                      {template.name}
                    </option>
                  ))}
                </select>
              </label>

              <label className="register-form__field">
                <span className="register-form__label">Audience</span>
                <select className="register-form__input" value={campaign.form.segmentKey} onChange={(event) => campaign.setForm((prev) => ({ ...prev, segmentKey: event.target.value }))}>
                  {Object.entries(campaign.segments).map(([key, segment]) => (
                    <option key={key} value={key}>
                      {segment.label}
                    </option>
                  ))}
                </select>
              </label>
            </div>

            <div className="rounded-2xl border border-brand-100 bg-brand-50 px-4 py-4 text-sm text-stone-700">
              <strong className="block text-brand-900">{campaign.segments[campaign.form.segmentKey]?.label ?? 'Audience marketing'}</strong>
              <span>{campaign.segments[campaign.form.segmentKey]?.description ?? 'Choisissez une audience pour afficher son comportement cible.'}</span>
            </div>

            {(campaign.form.segmentKey === 'customers_with_orders' || campaign.form.segmentKey === 'loyal_customers') && (
              <label className="register-form__field">
                <span className="register-form__label">Nombre minimum de commandes</span>
                <input className="register-form__input" type="number" min={1} value={campaign.form.minimumOrders} onChange={(event) => campaign.setForm((prev) => ({ ...prev, minimumOrders: event.target.value }))} />
              </label>
            )}

            {campaign.form.segmentKey === 'inactive_customers' && (
              <label className="register-form__field">
                <span className="register-form__label">Inactivité en jours</span>
                <input className="register-form__input" type="number" min={30} value={campaign.form.inactiveDays} onChange={(event) => campaign.setForm((prev) => ({ ...prev, inactiveDays: event.target.value }))} />
              </label>
            )}

            {(campaign.form.segmentKey === 'recent_verified_users' || campaign.form.segmentKey === 'verified_without_orders_recent') && (
              <label className="register-form__field">
                <span className="register-form__label">Ancienneté maximale du compte en jours</span>
                <input className="register-form__input" type="number" min={7} value={campaign.form.registeredDays} onChange={(event) => campaign.setForm((prev) => ({ ...prev, registeredDays: event.target.value }))} />
              </label>
            )}

            {campaign.form.segmentKey === 'recent_customers' && (
              <label className="register-form__field">
                <span className="register-form__label">Commande au cours des X derniers jours</span>
                <input className="register-form__input" type="number" min={7} value={campaign.form.recentDays} onChange={(event) => campaign.setForm((prev) => ({ ...prev, recentDays: event.target.value }))} />
              </label>
            )}

            {campaign.form.segmentKey === 'high_value_customers' && (
              <label className="register-form__field">
                <span className="register-form__label">Montant cumulé minimum en centimes</span>
                <input className="register-form__input" type="number" min={1000} step={100} value={campaign.form.minimumTotalCents} onChange={(event) => campaign.setForm((prev) => ({ ...prev, minimumTotalCents: event.target.value }))} />
                <span className="text-xs text-stone-500">Exemple: 50000 = 500,00 EUR.</span>
              </label>
            )}

            {campaign.form.segmentKey === 'customers_with_pending_reviews' && (
              <label className="register-form__field">
                <span className="register-form__label">Nombre minimum d’avis en attente</span>
                <input className="register-form__input" type="number" min={1} value={campaign.form.minimumPendingReviews} onChange={(event) => campaign.setForm((prev) => ({ ...prev, minimumPendingReviews: event.target.value }))} />
              </label>
            )}

            <label className="register-form__field">
              <span className="register-form__label">Objet</span>
              <input className="register-form__input" value={campaign.form.subject} onChange={(event) => campaign.setForm((prev) => ({ ...prev, subject: event.target.value }))} />
            </label>

            <label className="register-form__field">
              <span className="register-form__label">HTML</span>
              <textarea className="register-form__input" rows={10} value={campaign.form.htmlBody} onChange={(event) => campaign.setForm((prev) => ({ ...prev, htmlBody: event.target.value }))} />
            </label>

            <label className="register-form__field">
              <span className="register-form__label">Texte brut</span>
              <textarea className="register-form__input" rows={6} value={campaign.form.textBody} onChange={(event) => campaign.setForm((prev) => ({ ...prev, textBody: event.target.value }))} />
            </label>

            <div className="flex flex-wrap gap-3">
              <button type="button" className="catalog-admin-actions__edit" onClick={() => void campaign.handlePreview()} disabled={campaign.previewLoading}>
                {campaign.previewLoading ? 'Prévisualisation...' : 'Prévisualiser l’audience'}
              </button>
              <button type="submit" className="register-form__submit" disabled={campaign.saving}>
                {campaign.saving ? 'Envoi...' : 'Envoyer la campagne'}
              </button>
            </div>
          </form>

          <div className="space-y-6">
            <div className="register-form-card form-card-grid">
              <h2 className="text-xl font-semibold text-brand-900">Audience</h2>
              <p className="text-sm text-stone-500">
                {campaign.segments[campaign.form.segmentKey]?.description ?? 'Choisissez une audience.'}
              </p>
              {campaign.preview ? (
                <>
                  <div className="rounded-2xl bg-brand-50 px-4 py-3 text-sm text-stone-700">
                    <strong>{campaign.preview.count}</strong> destinataire(s). {campaign.preview.description}
                  </div>
                  <div className="space-y-2">
                    {campaign.preview.recipients.map((recipient) => (
                      <div key={recipient.id} className="rounded-xl border border-brand-100 px-3 py-2 text-sm">
                        <strong>{recipient.fullName}</strong>
                        <div className="text-stone-500">{recipient.email}</div>
                      </div>
                    ))}
                  </div>
                </>
              ) : (
                <p className="text-sm text-stone-500">Lancez une prévisualisation pour voir le volume et quelques exemples.</p>
              )}
            </div>

            <div className="register-form-card form-card-grid">
              <h2 className="text-xl font-semibold text-brand-900">Leviers conseillés</h2>
              <div className="space-y-2 text-sm text-stone-600">
                {campaign.audienceAdvice.map((item) => (
                  <div key={item} className="rounded-2xl border border-brand-100 bg-brand-50 px-4 py-3">
                    {item}
                  </div>
                ))}
              </div>
            </div>

            <div className="register-form-card form-card-grid">
              <div className="flex items-center justify-between gap-3">
                <h2 className="text-xl font-semibold text-brand-900">Templates recommandés</h2>
                <Link to="/admin/marketing/templates" className="text-sm font-semibold text-stone-700 hover:text-brand-900">
                  Voir toute la bibliothèque
                </Link>
              </div>
              {campaign.templatesForSegment.length === 0 ? (
                <EmptyState>Aucun template actif n’est encore associé à cette audience.</EmptyState>
              ) : (
                <div className="space-y-3">
                  {campaign.templatesForSegment.slice(0, 4).map((template) => (
                    <div key={template.id} className="rounded-2xl border border-brand-100 px-4 py-4">
                      <div className="flex items-start justify-between gap-3">
                        <div>
                          <strong className="block text-brand-900">{template.name}</strong>
                          <div className="mt-1 text-sm text-stone-500">{template.subjectTemplate}</div>
                        </div>
                        <button
                          type="button"
                          className="catalog-admin-actions__edit"
                          onClick={() => campaign.setForm((prev) => ({ ...prev, templateId: String(template.id) }))}
                        >
                          Utiliser
                        </button>
                      </div>
                      <div className="mt-3 flex flex-wrap gap-3 text-sm">
                        <Link to={`/admin/marketing/templates/${template.id}`} className="font-semibold text-stone-700 hover:text-brand-900">
                          Voir le détail
                        </Link>
                        <Link to={`/admin/marketing/templates/${template.id}/edit`} className="font-semibold text-stone-500 hover:text-stone-700">
                          Modifier
                        </Link>
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </div>
          </div>
        </div>
      )}
    </PageContainer>
  );
};
