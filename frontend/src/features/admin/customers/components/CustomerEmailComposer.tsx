import { type AdminCustomerDetailDto } from '@/features/admin/customers/api';
import {
  customerEmailPresets,
  type CustomerEmailFormState,
  type EmailTemplatePreset,
} from './customerDetailShared';

export const CustomerEmailComposer = ({
  customer,
  emailForm,
  emailOnlyView,
  emailSending,
  onApplyPreset,
  onClose,
  onEmailFormChange,
  onSendEmail,
}: {
  customer: AdminCustomerDetailDto;
  emailForm: CustomerEmailFormState;
  emailOnlyView: boolean;
  emailSending: boolean;
  onApplyPreset: (preset: EmailTemplatePreset) => void;
  onClose: () => void;
  onEmailFormChange: (form: CustomerEmailFormState) => void;
  onSendEmail: () => void;
}) => (
  <section className="overflow-hidden rounded-xl border border-brand-100 bg-white shadow-sm">
    <div className="border-b border-brand-100 bg-brand-900 px-6 py-5 text-white">
      <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div>
          <h2 className="text-xl font-semibold">Envoyer un e-mail manuel</h2>
          <p className="mt-2 text-sm text-stone-500">
            Message direct vers {customer.fullName} ({customer.email}).
          </p>
        </div>
        <div className="rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-stone-200">
          Utilise un modèle, ajuste le message puis envoie-le sans quitter cette fiche.
        </div>
      </div>
    </div>

    <div className="grid gap-6 px-6 py-6 xl:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)]">
      <div className="rounded-xl border border-brand-100 bg-brand-50 p-5">
        <div className="flex items-center justify-between gap-3">
          <div>
            <div className="text-sm font-semibold text-brand-900">Modèles prêts à l’emploi</div>
            <p className="mt-1 text-sm text-stone-500">
              Préremplis les cas les plus fréquents sans repartir de zéro.
            </p>
          </div>
          <span className="rounded-full bg-white px-3 py-1 text-xs font-medium text-stone-600">
            {customerEmailPresets.length} modèles
          </span>
        </div>
        <div className="mt-4 flex flex-wrap gap-2">
          {customerEmailPresets.map((preset) => (
            <button
              key={preset.id}
              type="button"
              className="rounded-full border border-brand-200 bg-white px-3 py-1.5 text-xs font-medium text-stone-700 transition hover:border-brand-600 hover:bg-brand-50"
              onClick={() => onApplyPreset(preset)}
            >
              {preset.label}
            </button>
          ))}
        </div>
      </div>

      <div className="space-y-4 rounded-xl border border-brand-100 p-5">
        <div>
          <div className="text-sm font-semibold text-brand-900">Composition du message</div>
          <p className="mt-1 text-sm text-stone-500">
            Le sujet et le contenu sont envoyés tels quels au client.
          </p>
        </div>
        <label className="register-form__field">
          <span className="register-form__label">Sujet</span>
          <input
            className="register-form__input"
            value={emailForm.subject}
            onChange={(event) => onEmailFormChange({ ...emailForm, subject: event.target.value })}
            placeholder="Sujet de l’email"
          />
        </label>
        <label className="register-form__field">
          <span className="register-form__label">Contenu</span>
          <textarea
            className="register-form__input"
            rows={10}
            value={emailForm.message}
            onChange={(event) => onEmailFormChange({ ...emailForm, message: event.target.value })}
            placeholder="Rédige ton message ici..."
          />
        </label>
        <div className="flex flex-wrap gap-3 pt-2">
          <button
            type="button"
            className="inline-flex items-center rounded-full bg-brand-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-800 disabled:opacity-60"
            onClick={onSendEmail}
            disabled={
              emailSending || emailForm.subject.trim() === '' || emailForm.message.trim() === ''
            }
          >
            {emailSending ? 'Envoi...' : 'Envoyer'}
          </button>
          {!emailOnlyView ? (
            <button
              type="button"
              className="inline-flex items-center rounded-full border border-brand-200 px-4 py-2 text-sm font-semibold text-stone-700 transition hover:border-brand-600"
              onClick={onClose}
            >
              Fermer
            </button>
          ) : null}
        </div>
      </div>
    </div>
  </section>
);
