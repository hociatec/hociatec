import { PublicPageSection } from '@/shared/components/layout/PublicPageShell';
import type { TradeInInput } from '../../types';
import { tradeInFieldClassName } from '../../lib/tradeInForm';
import { Check, Field, SectionHeading } from './TradeInFormParts';

interface TradeInDeviceFieldsProps {
  categories: [string, string][];
  conditions: [string, string][];
  form: TradeInInput;
  onChange: <K extends keyof TradeInInput>(key: K, value: TradeInInput[K]) => void;
}

export const TradeInDeviceFields = ({
  categories,
  conditions,
  form,
  onChange,
}: TradeInDeviceFieldsProps) => (
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
);
