export type SupportForm = { customerId: string; orderId: string; subject: string; reason: string; message: string; internalNotes: string };
export type RefundForm = { orderId: string; amountCents: string; reason: string; internalNotes: string };
export type StockForm = { productId: string; delta: string; reason: string; note: string };
export type BulkForm = { orderIds: string; status: string };
export type ShippingForms = Record<number, { carrier: string; trackingNumber: string; trackingUrl: string }>;
export type SupportReplies = Record<number, { subject: string; message: string }>;

