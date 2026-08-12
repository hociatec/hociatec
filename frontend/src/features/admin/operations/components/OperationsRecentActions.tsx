import type { Dispatch, SetStateAction } from 'react';

import type {
  RefundRequestDto,
  StockMovementDto,
  SupportRequestDto,
} from '@/features/admin/operations/api';
import { operationsUi } from '@/features/admin/operations/components/AdminOperationsWidgets';
import { Field } from '@/features/admin/operations/components/AdminOperationsWidgets';
import type { SupportReplies } from './operationsTypes';

const { inputClass, secondaryActionClass } = operationsUi;

export const SupportRequestAction = ({
  item,
  supportReplies,
  setSupportReplies,
  submitSupportReply,
  updateSupportStatus,
}: {
  item: SupportRequestDto;
  supportReplies: SupportReplies;
  setSupportReplies: Dispatch<SetStateAction<SupportReplies>>;
  submitSupportReply: (supportId: number) => void;
  updateSupportStatus: (supportId: number, status: string) => void;
}) => (
  <div className="space-y-2">
    <Field label="Changer le statut">
      <select
        className={inputClass}
        value={item.status}
        onChange={(event) => updateSupportStatus(item.id, event.target.value)}
      >
        <option value="new">Nouveau</option>
        <option value="in_progress">En cours</option>
        <option value="waiting_customer">En attente client</option>
        <option value="resolved">Résolu</option>
        <option value="refused">Refusé</option>
      </select>
    </Field>
    <input
      className={inputClass}
      placeholder="Sujet réponse client"
      value={supportReplies[item.id]?.subject ?? `Réponse SAV #${item.id}`}
      onChange={(event) =>
        setSupportReplies((current) => ({
          ...current,
          [item.id]: {
            subject: event.target.value,
            message: current[item.id]?.message ?? '',
          },
        }))
      }
    />
    <textarea
      className={inputClass}
      rows={2}
      placeholder="Message à envoyer au client"
      value={supportReplies[item.id]?.message ?? ''}
      onChange={(event) =>
        setSupportReplies((current) => ({
          ...current,
          [item.id]: {
            subject: current[item.id]?.subject ?? `Réponse SAV #${item.id}`,
            message: event.target.value,
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
);

export const RefundRequestAction = ({
  item,
  refundConfirmations,
  setRefundConfirmations,
  submitStripeRefund,
  updateRefundStatus,
}: {
  item: RefundRequestDto;
  refundConfirmations: Record<number, string>;
  setRefundConfirmations: Dispatch<SetStateAction<Record<number, string>>>;
  submitStripeRefund: (refundId: number) => void;
  updateRefundStatus: (refundId: number, status: string) => void;
}) => (
  <div className="space-y-2">
    <select
      className={inputClass}
      value={item.status}
      onChange={(event) => updateRefundStatus(item.id, event.target.value)}
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
      onChange={(event) =>
        setRefundConfirmations((current) => ({ ...current, [item.id]: event.target.value }))
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
);

export const StockThresholdAction = ({
  item,
  stockThresholds,
  setStockThresholds,
  submitStockThreshold,
}: {
  item: StockMovementDto;
  stockThresholds: Record<number, string>;
  setStockThresholds: Dispatch<SetStateAction<Record<number, string>>>;
  submitStockThreshold: (productId: number) => void;
}) => (
  <div className="flex flex-col gap-2 sm:flex-row">
    <input
      className={inputClass}
      inputMode="numeric"
      placeholder="Nouveau seuil stock faible"
      value={stockThresholds[item.product.id] ?? ''}
      onChange={(event) =>
        setStockThresholds((current) => ({
          ...current,
          [item.product.id]: event.target.value,
        }))
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
);
