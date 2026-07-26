import { type Dispatch, type SetStateAction } from 'react';

import {
  type EmailLogDto,
  type RefundRequestDto,
  type StockMovementDto,
  type SupportRequestDto,
} from '@/features/admin/operations/api';
import { List, operationsUi } from '@/features/admin/operations/components/AdminOperationsWidgets';
import { formatEuroCents, formatFrenchDateTime } from '@/shared/lib/formatters';
import { type SupportReplies } from './operationsTypes';
const { inputClass, secondaryActionClass } = operationsUi;

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
            <div className="space-y-2">
              <select
                className={inputClass}
                value={item.status}
                onChange={(e) => updateSupportStatus(item.id, e.target.value)}
              >
                <option value="new">Nouveau</option>
                <option value="in_progress">En cours</option>
                <option value="waiting_customer">En attente client</option>
                <option value="resolved">Résolu</option>
                <option value="refused">Refusé</option>
              </select>
              <input
                className={inputClass}
                placeholder="Sujet réponse client"
                value={supportReplies[item.id]?.subject ?? `Réponse SAV #${item.id}`}
                onChange={(e) =>
                  setSupportReplies((p) => ({
                    ...p,
                    [item.id]: { subject: e.target.value, message: p[item.id]?.message ?? '' },
                  }))
                }
              />
              <textarea
                className={inputClass}
                rows={2}
                placeholder="Message à envoyer au client"
                value={supportReplies[item.id]?.message ?? ''}
                onChange={(e) =>
                  setSupportReplies((p) => ({
                    ...p,
                    [item.id]: {
                      subject: p[item.id]?.subject ?? `Réponse SAV #${item.id}`,
                      message: e.target.value,
                    },
                  }))
                }
              />
              <button
                className={secondaryActionClass}
                type="button"
                onClick={() => submitSupportReply(item.id)}
                disabled={!supportReplies[item.id]?.message}
              >
                Répondre au client
              </button>
            </div>
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
            <div className="space-y-2">
              <select
                className={inputClass}
                value={item.status}
                onChange={(e) => updateRefundStatus(item.id, e.target.value)}
              >
                <option value="requested">Demandé</option>
                <option value="approved">Approuvé</option>
                <option value="rejected">Refusé</option>
                <option value="processed">Traité</option>
              </select>
              <input
                className={inputClass}
                placeholder="Tape REMBOURSER pour déclencher Stripe"
                value={refundConfirmations[item.id] ?? ''}
                onChange={(e) =>
                  setRefundConfirmations((p) => ({ ...p, [item.id]: e.target.value }))
                }
                disabled={Boolean(item.stripeRefundId) || item.status === 'processed'}
              />
              <button
                className={secondaryActionClass}
                type="button"
                onClick={() => submitStripeRefund(item.id)}
                disabled={
                  (refundConfirmations[item.id] ?? '') !== 'REMBOURSER' ||
                  Boolean(item.stripeRefundId) ||
                  item.status === 'processed'
                }
              >
                Déclencher remboursement Stripe
              </button>
              {item.stripeRefundId && (
                <p className="text-xs text-emerald-700">Stripe : {item.stripeRefundId}</p>
              )}
            </div>
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
            <div className="flex flex-col gap-2 sm:flex-row">
              <input
                className={inputClass}
                inputMode="numeric"
                placeholder="Nouveau seuil stock faible"
                value={stockThresholds[item.product.id] ?? ''}
                onChange={(e) =>
                  setStockThresholds((p) => ({ ...p, [item.product.id]: e.target.value }))
                }
              />
              <button
                className={secondaryActionClass}
                type="button"
                onClick={() => submitStockThreshold(item.product.id)}
                disabled={!stockThresholds[item.product.id]}
              >
                Modifier seuil
              </button>
            </div>
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
