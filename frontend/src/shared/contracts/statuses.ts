export const ORDER_STATUSES = ['pending', 'confirmed', 'delivered', 'cancelled'] as const;
export type OrderStatus = (typeof ORDER_STATUSES)[number];
export const ORDER_STATUS_FILTERS = ['all', ...ORDER_STATUSES] as const;
export type OrderStatusFilter = (typeof ORDER_STATUS_FILTERS)[number];

export const QUOTE_STATUSES = ['draft', 'sent', 'accepted', 'refused', 'expired'] as const;
export type QuoteStatus = (typeof QUOTE_STATUSES)[number];

export const AUDIT_STATUSES = ['requested', 'scheduled', 'in_progress', 'completed', 'cancelled'] as const;
export type AuditStatus = (typeof AUDIT_STATUSES)[number];

export const REFUND_STATUSES = ['pending', 'approved', 'rejected', 'refunded', 'failed'] as const;
export type RefundStatus = (typeof REFUND_STATUSES)[number];

export const MARKETING_CAMPAIGN_STATUSES = ['draft', 'queued', 'sending', 'sent', 'failed', 'cancelled'] as const;
export type MarketingCampaignStatus = (typeof MARKETING_CAMPAIGN_STATUSES)[number];

export const BETA_CAMPAIGN_STATUSES = ['draft', 'active', 'closed'] as const;
export type BetaCampaignStatus = (typeof BETA_CAMPAIGN_STATUSES)[number];

export const BUG_REPORT_STATUSES = ['open', 'awaiting_admin', 'awaiting_user', 'resolved', 'duplicate', 'rejected'] as const;
export type BugReportStatus = (typeof BUG_REPORT_STATUSES)[number];

export const TICKET_STATUSES = ['open', 'in_progress', 'resolved', 'closed', 'duplicate'] as const;
export type TicketStatus = (typeof TICKET_STATUSES)[number];

export const isContractValue = <T extends string>(
  values: readonly T[],
  value: string,
): value is T => (values as readonly string[]).includes(value);
