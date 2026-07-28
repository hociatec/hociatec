import { PublicPageSection } from '@/shared/components/layout/PublicPageShell';
import type { TradeInInput } from '../../types';
import { Field, SectionHeading } from './TradeInFormParts';

interface TradeInContactFieldsProps {
  form: TradeInInput;
  isAuthenticated: boolean;
  onChange: <K extends keyof TradeInInput>(key: K, value: TradeInInput[K]) => void;
}

export const TradeInContactFields = ({ form, isAuthenticated, onChange }: TradeInContactFieldsProps) => (
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
);
