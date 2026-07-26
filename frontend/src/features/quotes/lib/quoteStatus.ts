import type { QuoteStatus } from '../types/quoteTypes';

export const QUOTE_STATUS_LABELS: Record<QuoteStatus, string> = {
  draft: 'Brouillon',
  sent: 'Envoyé',
  accepted: 'Accepté',
  refused: 'Refusé',
  expired: 'Expiré',
};

export const formatQuoteStatus = (status?: string | null) => {
  if (!status) return '-';
  return QUOTE_STATUS_LABELS[status as QuoteStatus] ?? status;
};
