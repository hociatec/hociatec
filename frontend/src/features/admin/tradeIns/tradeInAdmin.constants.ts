import type { TradeInStatus } from '@/features/tradeIns/publicApi';

export const tradeInStatuses: TradeInStatus[] = [
  'submitted',
  'under_review',
  'offer_sent',
  'accepted',
  'declined',
  'received',
  'inspected',
  'completed',
  'cancelled',
  'expired',
];

export const tradeInStatusLabels: Record<TradeInStatus, string> = {
  submitted: 'Demande reçue',
  under_review: 'En cours d’étude',
  offer_sent: 'Offre envoyée',
  accepted: 'Acceptée',
  declined: 'Refusée',
  received: 'Matériel reçu',
  inspected: 'Matériel inspecté',
  completed: 'Reprise terminée',
  cancelled: 'Demande annulée',
  expired: 'Offre expirée',
};

export const tradeInConditionLabels: Record<string, string> = {
  comme_neuf: 'Comme neuf',
  tres_bon: 'Très bon état',
  bon: 'Bon état',
  correct: 'État correct',
  hors_service: 'Hors service / pour pièces',
};

export const tradeInNextStatuses: Record<TradeInStatus, TradeInStatus[]> = {
  submitted: ['submitted', 'under_review', 'offer_sent', 'cancelled'],
  under_review: ['under_review', 'offer_sent', 'cancelled'],
  offer_sent: ['offer_sent', 'accepted', 'declined', 'expired', 'cancelled'],
  accepted: ['accepted', 'received', 'cancelled'],
  declined: ['declined'],
  received: ['received', 'inspected', 'cancelled'],
  inspected: ['inspected', 'completed', 'cancelled'],
  completed: ['completed'],
  cancelled: ['cancelled'],
  expired: ['expired'],
};
