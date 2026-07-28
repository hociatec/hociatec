import { PublicPageSection } from '@/shared/components/layout/PublicPageShell';
import { FeedbackMessage } from '@/shared/components/ui/page-state';
import { formatEuroCents } from '@/shared/lib/formatters';
import type { TradeInDto } from '../types';

interface TradeInSuccessPanelProps {
  result: TradeInDto;
  resultMessage: string | null;
  onReset: () => void;
}

export const TradeInSuccessPanel = ({ result, resultMessage, onReset }: TradeInSuccessPanelProps) => (
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
      onClick={onReset}
    >
      Faire une autre demande
    </button>
  </PublicPageSection>
);
