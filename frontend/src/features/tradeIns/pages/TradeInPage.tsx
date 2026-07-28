import { useEffect, useState } from 'react';
import { SiteLayout } from '@/shared/components/layout/SiteLayout';
import { PublicPageSection, PublicPageShell } from '@/shared/components/layout/PublicPageShell';
import { FeedbackMessage } from '@/shared/components/ui/page-state';
import { useAuth } from '@/features/auth/hooks/useAuth';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import { formatEuroCents } from '@/shared/lib/formatters';
import { createTradeIn } from '../api';
import type { TradeInDto, TradeInInput } from '../types';
import { useTradeInMetadata } from '../useTradeInMetadata';

const emptyForm: TradeInInput = {
  firstName: '',
  lastName: '',
  email: '',
  phone: '',
  category: 'autre',
  productName: '',
  purchasePriceCents: 0,
  purchaseYear: new Date().getFullYear(),
  brand: '',
  model: '',
  serialNumber: '',
  conditionGrade: 'bon',
  functional: true,
  hasAccessories: false,
  hasProofOfPurchase: false,
  description: '',
  catalogProductId: null,
  consent: false,
  rib: null,
};

export const TradeInPage = () => {
  useDocumentTitle('Faire reprendre un matériel');
  const { user, status } = useAuth();
  const { categories, conditions } = useTradeInMetadata();
  const [form, setForm] = useState<TradeInInput>(emptyForm);
  const [result, setResult] = useState<TradeInDto | null>(null);
  const [resultMessage, setResultMessage] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [saving, setSaving] = useState(false);

  useEffect(() => {
    if (status === 'authenticated' && user) {
      setForm((current) => ({
        ...current,
        firstName: user.firstName,
        lastName: user.lastName,
        email: user.email,
        phone: user.phoneNumber,
      }));
    }
  }, [status, user]);

  const update = <K extends keyof TradeInInput>(key: K, value: TradeInInput[K]) =>
    setForm((current) => ({ ...current, [key]: value }));

  const submit = async (event: React.FormEvent) => {
    event.preventDefault();
    setError(null);
    setSaving(true);
    try {
      const response = await createTradeIn(form, status === 'authenticated');
      setResult(response.item);
      setResultMessage(response.message ?? null);
    } catch (submissionError) {
      setError(getHttpErrorMessage(submissionError));
    } finally {
      setSaving(false);
    }
  };

  if (result) {
    return (
      <SiteLayout headerVariant="light">
        <PublicPageShell
          size="medium"
          eyebrow="Reprise"
          title="Demande envoyée"
          description="Votre demande a été enregistrée. L’estimation sera confirmée après vérification."
        >
          <PublicPageSection className="space-y-4">
            <FeedbackMessage variant="success">
              {resultMessage ?? `Votre demande ${result.reference} a bien été enregistrée.`}
            </FeedbackMessage>
            <p className="text-stone-700">
              Notre estimation indicative se situe entre{' '}
              <strong>{formatEuroCents(result.estimatedMinCents)}</strong> et{' '}
              <strong>{formatEuroCents(result.estimatedMaxCents)}</strong>.
            </p>
            <p className="text-stone-600">
              Vous pourrez suivre la demande depuis votre espace client si vous avez un compte.
            </p>
            <button
              className="inline-flex items-center justify-center rounded-full bg-brand-900 px-6 py-3 text-sm font-semibold text-white transition hover:bg-brand-800"
              type="button"
              onClick={() => {
                setResult(null);
                setResultMessage(null);
                setForm(emptyForm);
              }}
            >
              Faire une autre demande
            </button>
          </PublicPageSection>
        </PublicPageShell>
      </SiteLayout>
    );
  }

  return (
    <SiteLayout headerVariant="light">
      <PublicPageShell
        eyebrow="Reprise matériel"
        title="Faire reprendre un matériel"
        description="Décrivez votre matériel. Vous recevrez une estimation indicative, puis une offre définitive après contrôle."
      >
        <div className="mx-auto w-full max-w-3xl space-y-6">
          {error ? <FeedbackMessage>{error}</FeedbackMessage> : null}
          <form className="space-y-6" onSubmit={submit}>
            <PublicPageSection className="space-y-5">
              <SectionHeading title="Vos coordonnées" />
              <div className="grid gap-4 md:grid-cols-2">
                <Field label="Prénom" value={form.firstName} onChange={(v) => update('firstName', v)} required />
                <Field label="Nom" value={form.lastName} onChange={(v) => update('lastName', v)} required />
                <Field label="Email" type="email" value={form.email} onChange={(v) => update('email', v)} required />
                <Field label="Téléphone" type="tel" value={form.phone} onChange={(v) => update('phone', v)} required />
              </div>
              {status === 'authenticated' ? (
                <p className="text-sm text-stone-600">Vos informations de compte ont été préremplies.</p>
              ) : null}
            </PublicPageSection>

            <PublicPageSection className="space-y-5">
              <SectionHeading
                title="Le matériel"
                description="L’estimation est calculée à partir du prix payé à l’achat, de l’année d’achat et de l’état du matériel."
              />
              <div className="grid gap-4 md:grid-cols-2">
                <label className="grid gap-1">
                  <span className="text-sm font-semibold text-brand-900">Catégorie</span>
                  <select
                    className={fieldClassName}
                    value={form.category}
                    onChange={(e) => update('category', e.target.value)}
                  >
                    {categories.map(([value, label]) => (
                      <option key={value} value={value}>
                        {label}
                      </option>
                    ))}
                  </select>
                </label>
                <Field
                  label="Nom du produit / modèle"
                  value={form.productName}
                  onChange={(v) => update('productName', v)}
                  placeholder="Ex. MacBook Pro 14 pouces M3"
                  required
                />
                <Field
                  label="Prix payé à l’achat (€)"
                  type="number"
                  value={form.purchasePriceCents > 0 ? String(form.purchasePriceCents / 100) : ''}
                  onChange={(v) =>
                    update('purchasePriceCents', Math.round(Number(v.replace(',', '.')) * 100))
                  }
                  min="1"
                  step="0.01"
                  required
                />
                <Field
                  label="Année d’achat"
                  type="number"
                  value={String(form.purchaseYear)}
                  onChange={(v) => update('purchaseYear', Number(v))}
                  min="1980"
                  max={String(new Date().getFullYear())}
                  required
                />
                <Field label="Marque" value={form.brand} onChange={(v) => update('brand', v)} />
                <Field
                  label="Numéro de série (facultatif)"
                  value={form.serialNumber}
                  onChange={(v) => update('serialNumber', v)}
                />
              </div>
              <label className="grid gap-1">
                <span className="text-sm font-semibold text-brand-900">État général</span>
                <select
                  className={fieldClassName}
                  value={form.conditionGrade}
                  onChange={(e) => update('conditionGrade', e.target.value)}
                >
                  {conditions.map(([value, label]) => (
                    <option key={value} value={value}>
                      {label}
                    </option>
                  ))}
                </select>
              </label>
              <div className="grid gap-3 md:grid-cols-3">
                <Check label="Le matériel fonctionne" checked={form.functional} onChange={(v) => update('functional', v)} />
                <Check label="Accessoires présents" checked={form.hasAccessories} onChange={(v) => update('hasAccessories', v)} />
                <Check label="Preuve d’achat disponible" checked={form.hasProofOfPurchase} onChange={(v) => update('hasProofOfPurchase', v)} />
              </div>
              <label className="grid gap-1">
                <span className="text-sm font-semibold text-brand-900">Description et défauts constatés</span>
                <textarea
                  className={fieldClassName}
                  rows={5}
                  maxLength={5000}
                  required
                  value={form.description}
                  onChange={(e) => update('description', e.target.value)}
                  placeholder="Décrivez précisément l'état, les défauts et les accessoires inclus."
                />
              </label>
            </PublicPageSection>

            <PublicPageSection className="space-y-5">
              <SectionHeading
                title="Règlement de la reprise"
                description="Votre RIB est utilisé uniquement si la reprise est validée et stocké de manière sécurisée. Format PDF uniquement, 5 Mo maximum."
              />
              <label className="grid gap-1">
                <span className="text-sm font-semibold text-brand-900">RIB au format PDF</span>
                <input
                  className={fieldClassName}
                  type="file"
                  accept="application/pdf,.pdf"
                  required
                  onChange={(e) => update('rib', e.target.files?.[0] ?? null)}
                />
              </label>
            </PublicPageSection>

            <label className="flex items-start gap-3 rounded-2xl border border-brand-100 bg-white p-4 text-sm text-stone-700 shadow-sm">
              <input
                type="checkbox"
                checked={form.consent}
                onChange={(e) => update('consent', e.target.checked)}
                required
              />
              <span>
                J’accepte que Hociatec utilise ces informations pour étudier ma demande de reprise
                et me recontacter.
              </span>
            </label>
            <button
              className="inline-flex items-center justify-center rounded-full bg-brand-900 px-6 py-3 text-sm font-semibold text-white transition hover:bg-brand-800 disabled:cursor-not-allowed disabled:opacity-60"
              type="submit"
              disabled={saving || !form.consent || !form.rib}
            >
              {saving ? 'Envoi en cours…' : 'Obtenir mon estimation'}
            </button>
          </form>
        </div>
      </PublicPageShell>
    </SiteLayout>
  );
};

const fieldClassName =
  'w-full rounded-xl border border-brand-100 bg-white px-4 py-3 text-brand-900 outline-none transition focus:border-brand-400 focus:ring-4 focus:ring-brand-100';

const SectionHeading = ({ title, description }: { title: string; description?: string }) => (
  <div>
    <h2 className="text-xl font-semibold text-brand-900">{title}</h2>
    {description ? <p className="mt-1 text-sm text-stone-600">{description}</p> : null}
  </div>
);

const Field = ({
  label,
  value,
  onChange,
  type = 'text',
  placeholder,
  required = false,
  min,
  max,
  step,
}: {
  label: string;
  value: string;
  onChange: (value: string) => void;
  type?: string;
  placeholder?: string;
  required?: boolean;
  min?: string;
  max?: string;
  step?: string;
}) => (
  <label className="grid gap-1">
    <span className="text-sm font-semibold text-brand-900">{label}</span>
    <input
      className={fieldClassName}
      type={type}
      value={value}
      placeholder={placeholder}
      required={required}
      min={min}
      max={max}
      step={step}
      onChange={(e) => onChange(e.target.value)}
    />
  </label>
);

const Check = ({
  label,
  checked,
  onChange,
}: {
  label: string;
  checked: boolean;
  onChange: (value: boolean) => void;
}) => (
  <label className="flex items-start gap-2 rounded-xl border border-brand-100 bg-brand-50 p-3 text-sm text-stone-700">
    <input type="checkbox" checked={checked} onChange={(e) => onChange(e.target.checked)} />
    <span>{label}</span>
  </label>
);
