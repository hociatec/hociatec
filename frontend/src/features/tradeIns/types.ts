export type TradeInStatus =
  | 'submitted'
  | 'under_review'
  | 'offer_sent'
  | 'accepted'
  | 'declined'
  | 'received'
  | 'inspected'
  | 'completed'
  | 'cancelled'
  | 'expired';

export interface TradeInContact { firstName: string; lastName: string; email: string; phone: string }
export interface TradeInDto {
  id: number;
  reference: string;
  status: TradeInStatus;
  statusLabel: string;
  allowedNextStatuses: TradeInStatus[];
  category: string;
  productName: string;
  purchasePriceCents: number;
  purchaseYear: number;
  brand: string | null;
  model: string | null;
  conditionGrade: string;
  conditionLabel: string;
  functional: boolean;
  hasAccessories: boolean;
  hasProofOfPurchase: boolean;
  description: string;
  catalogProductId?: number | null;
  catalogProductName?: string | null;
  estimatedMinCents: number;
  estimatedMaxCents: number;
  offerCents: number | null;
  adminNote: string | null;
  offerExpiresAt: string | null;
  createdAt: string;
  contact?: TradeInContact;
  finalOfferCents?: number | null;
  paymentMethod?: string | null;
  paymentStatus?: string;
  transactionReference?: string | null;
  paidAt?: string | null;
  ribAvailable?: boolean;
  ribOriginalName?: string | null;
  receiptAvailable?: boolean;
  voucherCode?: string | null;
  closedAt?: string | null;
}

export interface TradeInInput {
  firstName: string;
  lastName: string;
  email: string;
  phone: string;
  category: string;
  productName: string;
  purchasePriceCents: number;
  purchaseYear: number;
  brand: string;
  model: string;
  serialNumber: string;
  conditionGrade: string;
  functional: boolean;
  hasAccessories: boolean;
  hasProofOfPurchase: boolean;
  description: string;
  catalogProductId: number | null;
  consent: boolean;
  rib: File | null;
}
