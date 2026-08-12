import { type Dispatch, type SetStateAction } from 'react';
import { Link } from 'react-router';

import {
  type EmailLogDto,
  type RefundRequestDto,
  type StockMovementDto,
  type SupportRequestDto,
} from '@/features/admin/operations/api';
import { List } from '@/features/admin/operations/components/AdminOperationsWidgets';
import { formatEuroCents, formatFrenchDateTime } from '@/shared/lib/formatters';
import type { PaginationMeta } from '@/shared/types/api';
import {
  RefundRequestAction,
  StockThresholdAction,
} from './OperationsRecentActions';
import type { SupportTimelineEntryDto } from '@/features/admin/operations/operationsApiShared';

type OperationsRecentMode = 'all' | 'support' | 'refunds';

const SupportTimeline = ({ timeline }: { timeline: SupportTimelineEntryDto[] }) => {
  if (timeline.length === 0) {
    return null;
  }

  return (
    <div className="rounded-xl border border-brand-100 bg-white p-3">
      <p className="text-sm font-medium text-brand-900">Historique</p>
      <div className="mt-3 space-y-3">
        {timeline.map((entry) => (
          <div key={entry.id} className="border-l-2 border-brand-100 pl-3">
            <p className="text-xs text-stone-500">
              {entry.authorLabel} · {formatFrenchDateTime(entry.createdAt)}
            </p>
            {entry.subject ? <p className="mt-1 text-sm font-medium text-brand-900">{entry.subject}</p> : null}
            {entry.message ? <p className="mt-1 text-sm text-stone-700 whitespace-pre-wrap">{entry.message}</p> : null}
            {entry.statusLabel ? <p className="mt-1 text-xs text-stone-500">Statut : {entry.statusLabel}</p> : null}
          </div>
        ))}
      </div>
    </div>
  );
};

export const OperationsRecentSection = ({
  emails,
  emailsMeta,
  mode = 'all',
  refundConfirmations,
  refunds,
  refundsMeta,
  setEmailsPage,
  setRefundsPage,
  setStockPage,
  setRefundConfirmations,
  setStockThresholds,
  stock,
  stockMeta,
  stockThresholds,
  submitStockThreshold,
  submitStripeRefund,
  support,
  supportMeta,
  setSupportPage,
  updateRefundStatus,
}: {
  emails: EmailLogDto[];
  emailsMeta: PaginationMeta;
  mode?: OperationsRecentMode;
  refundConfirmations: Record<number, string>;
  refunds: RefundRequestDto[];
  refundsMeta: PaginationMeta;
  setEmailsPage: Dispatch<SetStateAction<number>>;
  setRefundConfirmations: Dispatch<SetStateAction<Record<number, string>>>;
  setRefundsPage: Dispatch<SetStateAction<number>>;
  setStockPage: Dispatch<SetStateAction<number>>;
  setStockThresholds: Dispatch<SetStateAction<Record<number, string>>>;
  stock: StockMovementDto[];
  stockMeta: PaginationMeta;
  stockThresholds: Record<number, string>;
  submitStockThreshold: (productId: number) => void;
  submitStripeRefund: (refundId: number) => void;
  support: SupportRequestDto[];
  supportMeta: PaginationMeta;
  setSupportPage: Dispatch<SetStateAction<number>>;
  updateRefundStatus: (refundId: number, status: string) => void;
}) => {
  if (mode === 'support') {
    return (
      <section className="mb-8">
        <List
          meta={supportMeta}
          onPageChange={setSupportPage}
          items={support.map((item) => ({
            key: item.id,
            title: (
              <>
                <p className="text-xs text-stone-500">Dossier #{item.id}</p>
                <p className="mt-1">{item.subject}</p>
              </>
            ),
            meta: (
              <>
                <p>{item.customer.name} · {formatFrenchDateTime(item.updatedAt)}</p>
                <p className="mt-1 text-stone-700">
                  <span className="sr-only">Statut :</span>
                  {item.statusLabel}
                </p>
                {item.awaitingReplyLabel ? <p className="mt-1 text-xs font-medium text-brand-800">{item.awaitingReplyLabel}</p> : null}
              </>
            ),
            action: (
              <div className="space-y-3">
                <Link className="inline-flex text-sm font-medium text-brand-700 underline" to={`/admin/customers/support/${item.id}`}>
                  Ouvrir le dossier complet
                </Link>
                <SupportTimeline timeline={item.timeline} />
              </div>
            ),
          }))}
        />
      </section>
    );
  }

  if (mode === 'refunds') {
    return (
      <section className="mb-8">
        <List
          meta={refundsMeta}
          onPageChange={setRefundsPage}
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
      </section>
    );
  }

  return (
    <section className="mb-8">
      <div className="mb-3">
        <h2 className="text-lg font-semibold text-brand-900">Suivi récent</h2>
        <p className="text-sm text-stone-500">Les dernières demandes et opérations à contrôler.</p>
      </div>
      <div className="grid gap-6 xl:grid-cols-2">
        <List
          title="Demandes SAV"
          meta={supportMeta}
          onPageChange={setSupportPage}
          items={support.map((item) => ({
            key: item.id,
            title: (
              <>
                <p className="text-xs text-stone-500">Dossier #{item.id}</p>
                <p className="mt-1">{item.subject}</p>
              </>
            ),
            meta: (
              <>
                <p>{item.customer.name} · {formatFrenchDateTime(item.updatedAt)}</p>
                <p className="mt-1 text-stone-700">
                  <span className="sr-only">Statut :</span>
                  {item.statusLabel}
                </p>
                {item.awaitingReplyLabel ? <p className="mt-1 text-xs font-medium text-brand-800">{item.awaitingReplyLabel}</p> : null}
              </>
            ),
            action: (
              <div className="space-y-3">
                <Link className="inline-flex text-sm font-medium text-brand-700 underline" to={`/admin/customers/support/${item.id}`}>
                  Ouvrir le dossier complet
                </Link>
                <SupportTimeline timeline={item.timeline} />
              </div>
            ),
          }))}
        />
        <List
          title="Remboursements"
          meta={refundsMeta}
          onPageChange={setRefundsPage}
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
          meta={stockMeta}
          onPageChange={setStockPage}
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
          meta={emailsMeta}
          onPageChange={setEmailsPage}
          items={emails.map((item, index) => ({
            key: `${item.createdAt}-${index}`,
            title: `${item.statusLabel ?? (item.status === 'failed' ? 'Échec' : 'Envoyé')} · ${item.scenarioLabel ?? item.scenario}`,
            meta: `${item.recipient || 'Destinataire inconnu'} · ${item.related?.label || item.subject || ''} · ${formatFrenchDateTime(item.createdAt)}`,
          }))}
        />
      </div>
    </section>
  );
};
