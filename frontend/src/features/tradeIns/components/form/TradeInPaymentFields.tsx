import { PublicPageSection } from '@/shared/components/layout/PublicPageShell';
import type { TradeInInput } from '../../types';
import { tradeInFieldClassName } from '../../lib/tradeInForm';
import { SectionHeading } from './TradeInFormParts';

interface TradeInPaymentFieldsProps {
  onChange: <K extends keyof TradeInInput>(key: K, value: TradeInInput[K]) => void;
}

export const TradeInPaymentFields = ({ onChange }: TradeInPaymentFieldsProps) => (
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
);
