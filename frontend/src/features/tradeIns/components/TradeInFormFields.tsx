import { PublicPageSection } from '@/shared/components/layout/PublicPageShell';
import type { TradeInInput } from '../types';
import { tradeInFieldClassName } from '../lib/tradeInForm';

interface TradeInFormFieldsProps {
  categories: [string, string][];
  conditions: [string, string][];
  form: TradeInInput;
  isAuthenticated: boolean;
  saving: boolean;
  onChange: <K extends keyof TradeInInput>(key: K, value: TradeInInput[K]) => void;
}

export const TradeInFormFields = ({
  categories,
  conditions,
  form,
  isAuthenticated,
  saving,
  onChange,
}: TradeInFormFieldsProps) => (
  <>
    <PublicPageSection className="space-y-5">
      <SectionHeading title="Vos coordonnées" />
      <div className="grid gap-4 md:grid-cols-2">
        <Field label="Prénom" value={form.firstName} onChange={(value) => onChange('firstName', value)} required />
        <Field label="Nom" value={form.lastName} onChange={(value) => onChange('lastName', value)} required />
        <Field label="Email" type="email" value={form.email} onChange={(value) => onChange('email', value)} required />
        <Field label="Téléphone" type="tel" value={form.phone} onChange={(value) => onChange('phone', value)} required />
      </div>
      {isAuthenticated ? (
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
            className={tradeInFieldClassName}
            value={form.category}
            onChange={(event) => onChange('category', event.target.value)}
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
          onChange={(value) => onChange('productName', value)}
          placeholder="Ex. MacBook Pro 14 pouces M3"
          required
        />
        <Field
          label="Prix payé à l’achat (€)"
          type="number"
          value={form.purchasePriceCents > 0 ? String(form.purchasePriceCents / 100) : ''}
          onChange={(value) => onChange('purchasePriceCents', Math.round(Number(value.replace(',', '.')) * 100))}
          min="1"
          step="0.01"
          required
        />
        <Field
          label="Année d’achat"
          type="number"
          value={String(form.purchaseYear)}
          onChange={(value) => onChange('purchaseYear', Number(value))}
          min="1980"
          max={String(new Date().getFullYear())}
          required
        />
        <Field label="Marque" value={form.brand} onChange={(value) => onChange('brand', value)} />
        <Field
          label="Numéro de série (facultatif)"
          value={form.serialNumber}
          onChange={(value) => onChange('serialNumber', value)}
        />
      </div>
      <label className="grid gap-1">
        <span className="text-sm font-semibold text-brand-900">État général</span>
        <select
          className={tradeInFieldClassName}
          value={form.conditionGrade}
          onChange={(event) => onChange('conditionGrade', event.target.value)}
        >
          {conditions.map(([value, label]) => (
            <option key={value} value={value}>
              {label}
            </option>
          ))}
        </select>
      </label>
      <div className="grid gap-3 md:grid-cols-3">
        <Check label="Le matériel fonctionne" checked={form.functional} onChange={(value) => onChange('functional', value)} />
        <Check label="Accessoires présents" checked={form.hasAccessories} onChange={(value) => onChange('hasAccessories', value)} />
        <Check label="Preuve d’achat disponible" checked={form.hasProofOfPurchase} onChange={(value) => onChange('hasProofOfPurchase', value)} />
      </div>
      <label className="grid gap-1">
        <span className="text-sm font-semibold text-brand-900">Description et défauts constatés</span>
        <textarea
          className={tradeInFieldClassName}
          rows={5}
          maxLength={5000}
          required
          value={form.description}
          onChange={(event) => onChange('description', event.target.value)}
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
          className={tradeInFieldClassName}
          type="file"
          accept="application/pdf,.pdf"
          required
          onChange={(event) => onChange('rib', event.target.files?.[0] ?? null)}
        />
      </label>
    </PublicPageSection>

    <label className="flex items-start gap-3 rounded-2xl border border-brand-100 bg-white p-4 text-sm text-stone-700 shadow-sm">
      <input
        type="checkbox"
        checked={form.consent}
        onChange={(event) => onChange('consent', event.target.checked)}
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
  </>
);

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
      className={tradeInFieldClassName}
      type={type}
      value={value}
      placeholder={placeholder}
      required={required}
      min={min}
      max={max}
      step={step}
      onChange={(event) => onChange(event.target.value)}
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
    <input type="checkbox" checked={checked} onChange={(event) => onChange(event.target.checked)} />
    <span>{label}</span>
  </label>
);
