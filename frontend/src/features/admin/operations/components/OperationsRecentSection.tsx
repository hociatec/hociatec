import { type Dispatch, type SetStateAction } from 'react';

import {
  type EmailLogDto,
  type RefundRequestDto,
  type StockMovementDto,
  type SupportRequestDto,
} from '@/features/admin/operations/api';
import { List } from '@/features/admin/operations/components/AdminOperationsWidgets';
import { formatEuroCents, formatFrenchDateTime } from '@/shared/lib/formatters';
import { type SupportReplies } from './operationsTypes';
import {
  RefundRequestAction,
  StockThresholdAction,
  SupportRequestAction,
} from './OperationsRecentActions';

export const OperationsRecentSection = ({
  emails,
  refundConfirmations,
  refunds,
  setRefundConfirmations,
  setStockThresholds,
  setSupportReplies,
  stock,
  stockThresholds,
  submitStockThreshold,
  submitStripeRefund,
  submitSupportReply,
  support,
  supportReplies,
  updateRefundStatus,
  updateSupportStatus,
}: {
  emails: EmailLogDto[];
  refundConfirmations: Record<number, string>;
  refunds: RefundRequestDto[];
  setRefundConfirmations: Dispatch<SetStateAction<Record<number, string>>>;
  setStockThresholds: Dispatch<SetStateAction<Record<number, string>>>;
  setSupportReplies: Dispatch<SetStateAction<SupportReplies>>;
  stock: StockMovementDto[];
  stockThresholds: Record<number, string>;
  submitStockThreshold: (productId: number) => void;
  submitStripeRefund: (refundId: number) => void;
  submitSupportReply: (supportId: number) => void;
  support: SupportRequestDto[];
  supportReplies: SupportReplies;
  updateRefundStatus: (refundId: number, status: string) => void;
  updateSupportStatus: (supportId: number, status: string) => void;
}) => (
  <section className="mb-8">
    <div className="mb-3">
      <h2 className="text-lg font-semibold text-brand-900">Suivi récent</h2>
      <p className="text-sm text-stone-500">Les dernières demandes et opérations à contrôler.</p>
    </div>
    <div className="grid gap-6 xl:grid-cols-2">
      <List
        title="Demandes SAV"
        items={support.map((item) => ({
          key: item.id,
          title: `#${item.id} · ${item.subject}`,
          meta: `${item.customer.name} · ${item.statusLabel} · ${formatFrenchDateTime(item.updatedAt)}`,
          action: (
            <SupportRequestAction
              item={item}
              supportReplies={supportReplies}
              setSupportReplies={setSupportReplies}
              submitSupportReply={submitSupportReply}
              updateSupportStatus={updateSupportStatus}
            />
          ),
        }))}
      />
      <List
        title="Remboursements"
        items={refunds.map((item) => ({
          key: item.id,
          title: `#${item.id} · ${item.order.number} · ${formatEuroCents(item.amountCents)}`,
          meta: `${item.status} · ${item.reason || 'Sans motif'} · ${formatFrenchDateTime(item.updatedAt)}`,
          action: (
            <RefundRequestAction
              item={item}
              refundConfirmations={refundConfirmations}
              setRefundConfirmations={setRefundConfirmations}
              submitStripeRefund={submitStripeRefund}
              updateRefundStatus={updateRefundStatus}
            />
          ),
        }))}
      />
      <List
        title="Mouvements de stock"
        items={stock.map((item) => ({
          key: item.id,
          title: `${item.product.sku} · ${item.product.name}`,
          meta: `${item.delta > 0 ? '+' : ''}${item.delta} · ${item.stockBefore} → ${item.stockAfter} · ${formatFrenchDateTime(item.createdAt)}`,
          action: (
            <StockThresholdAction
              item={item}
              stockThresholds={stockThresholds}
              setStockThresholds={setStockThresholds}
              submitStockThreshold={submitStockThreshold}
            />
          ),
        }))}
      />
      <List
        title="Emails transactionnels"
        items={emails.map((item, index) => ({
          key: `${item.createdAt}-${index}`,
          title: `${item.statusLabel ?? (item.status === 'failed' ? 'Échec' : 'Envoyé')} · ${item.scenarioLabel ?? item.scenario}`,
          meta: `${item.recipient || 'Destinataire inconnu'} · ${item.related?.label || item.subject || ''} · ${formatFrenchDateTime(item.createdAt)}`,
        }))}
      />
    </div>
  </section>
);
