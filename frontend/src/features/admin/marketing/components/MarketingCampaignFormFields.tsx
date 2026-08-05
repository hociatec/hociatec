import type { useMarketingCampaignForm } from '@/features/admin/marketing/hooks/useMarketingCampaignForm';
import { formatEuroCents } from '@/shared/lib/formatters';

type CampaignController = ReturnType<typeof useMarketingCampaignForm>;

export const MarketingCampaignFormFields = ({ campaign }: { campaign: CampaignController }) => (
  <>
    <label className="register-form__field">
      <span className="register-form__label">Nom de campagne</span>
      <input
        className="register-form__input"
        value={campaign.form.name}
        onChange={(event) => campaign.setForm((prev) => ({ ...prev, name: event.target.value }))}
      />
    </label>

    <div className="grid gap-4 md:grid-cols-2">
      <label className="register-form__field">
        <span className="register-form__label">Template</span>
        <select
          className="register-form__input"
          value={campaign.form.templateId}
          onChange={(event) =>
            campaign.setForm((prev) => ({ ...prev, templateId: event.target.value }))
          }
        >
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
        <select
          className="register-form__input"
          value={campaign.form.segmentKey}
          onChange={(event) =>
            campaign.setForm((prev) => ({ ...prev, segmentKey: event.target.value }))
          }
        >
          {Object.entries(campaign.segments).map(([key, segment]) => (
            <option key={key} value={key}>
              {segment.label}
            </option>
          ))}
        </select>
      </label>
    </div>

    <div className="rounded-2xl border border-brand-100 bg-brand-50 px-4 py-4 text-sm text-stone-700">
      <strong className="block text-brand-900">
        {campaign.segments[campaign.form.segmentKey]?.label ?? 'Audience marketing'}
      </strong>
      <span>
        {campaign.segments[campaign.form.segmentKey]?.description ??
          'Choisissez une audience pour afficher son comportement cible.'}
      </span>
    </div>

    {(campaign.form.segmentKey === 'customers_with_orders' ||
      campaign.form.segmentKey === 'loyal_customers') && (
      <label className="register-form__field">
        <span className="register-form__label">Nombre minimum de commandes</span>
        <input
          className="register-form__input"
          type="number"
          min={1}
          value={campaign.form.minimumOrders}
          onChange={(event) =>
            campaign.setForm((prev) => ({ ...prev, minimumOrders: event.target.value }))
          }
        />
      </label>
    )}

    {campaign.form.segmentKey === 'inactive_customers' && (
      <label className="register-form__field">
        <span className="register-form__label">Inactivité en jours</span>
        <input
          className="register-form__input"
          type="number"
          min={30}
          value={campaign.form.inactiveDays}
          onChange={(event) =>
            campaign.setForm((prev) => ({ ...prev, inactiveDays: event.target.value }))
          }
        />
      </label>
    )}

    {(campaign.form.segmentKey === 'recent_verified_users' ||
      campaign.form.segmentKey === 'verified_without_orders_recent') && (
      <label className="register-form__field">
        <span className="register-form__label">Ancienneté maximale du compte en jours</span>
        <input
          className="register-form__input"
          type="number"
          min={7}
          value={campaign.form.registeredDays}
          onChange={(event) =>
            campaign.setForm((prev) => ({ ...prev, registeredDays: event.target.value }))
          }
        />
      </label>
    )}

    {campaign.form.segmentKey === 'recent_customers' && (
      <label className="register-form__field">
        <span className="register-form__label">Commande au cours des X derniers jours</span>
        <input
          className="register-form__input"
          type="number"
          min={7}
          value={campaign.form.recentDays}
          onChange={(event) =>
            campaign.setForm((prev) => ({ ...prev, recentDays: event.target.value }))
          }
        />
      </label>
    )}

    {campaign.form.segmentKey === 'high_value_customers' && (
      <label className="register-form__field">
        <span className="register-form__label">Montant cumulé minimum en centimes</span>
        <input
          className="register-form__input"
          type="number"
          min={1000}
          step={100}
          value={campaign.form.minimumTotalCents}
          onChange={(event) =>
            campaign.setForm((prev) => ({ ...prev, minimumTotalCents: event.target.value }))
          }
        />
        <span className="text-xs text-stone-500">Exemple: 50000 = {formatEuroCents(50000)}.</span>
      </label>
    )}

    {campaign.form.segmentKey === 'customers_with_pending_reviews' && (
      <label className="register-form__field">
        <span className="register-form__label">Nombre minimum d’avis en attente</span>
        <input
          className="register-form__input"
          type="number"
          min={1}
          value={campaign.form.minimumPendingReviews}
          onChange={(event) =>
            campaign.setForm((prev) => ({ ...prev, minimumPendingReviews: event.target.value }))
          }
        />
      </label>
    )}

    <label className="register-form__field">
      <span className="register-form__label">Objet</span>
      <input
        className="register-form__input"
        value={campaign.form.subject}
        onChange={(event) => campaign.setForm((prev) => ({ ...prev, subject: event.target.value }))}
      />
    </label>

    <label className="register-form__field">
      <span className="register-form__label">HTML</span>
      <textarea
        className="register-form__input"
        rows={10}
        value={campaign.form.htmlBody}
        onChange={(event) => campaign.setForm((prev) => ({ ...prev, htmlBody: event.target.value }))}
      />
    </label>

    <label className="register-form__field">
      <span className="register-form__label">Texte brut</span>
      <textarea
        className="register-form__input"
        rows={6}
        value={campaign.form.textBody}
        onChange={(event) => campaign.setForm((prev) => ({ ...prev, textBody: event.target.value }))}
      />
    </label>
  </>
);
