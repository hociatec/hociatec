import { useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { SiteLayout } from '@/shared/components/layout/SiteLayout';
import { PublicPageSection, PublicPageShell } from '@/shared/components/layout/PublicPageShell';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { EmptyState, ErrorState, LoadingState, FeedbackMessage } from '@/shared/components/ui/page-state';
import { formatEuroCents, formatFrenchDate } from '@/shared/lib/formatters';
import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import { downloadMyTradeInReceipt, fetchMyTradeIns, respondToTradeIn } from '../api';
import { downloadBlob } from '@/shared/lib/downloadFile';
import type { TradeInDto } from '../types';
import { tradeInQueryKeys } from '@/features/tradeIns/queryKeys';
import { PaginationControls } from '@/shared/components/ui/PaginationControls';
import type { PaginatedResult } from '@/shared/types/api';

export const MyTradeInsPage = () => {
  useDocumentTitle('Mes reprises');
  const queryClient = useQueryClient();
  const [page, setPage] = useState(1);
  const tradeInsQuery = useQuery<PaginatedResult<TradeInDto>, Error>({
    queryKey: [...tradeInQueryKeys.mine(), { page }],
    queryFn: () => fetchMyTradeIns(page, 10),
  });
  const [error, setError] = useState<string | null>(null);
  const [message, setMessage] = useState<string | null>(null);
  const items = tradeInsQuery.data?.items ?? [];
  const pagination = tradeInsQuery.data?.meta ?? { page, perPage: 10, total: 0, totalPages: 1 };

  const responseMutation = useMutation({
    mutationFn: ({ id, action }: { id: number; action: 'accept' | 'decline' }) =>
      respondToTradeIn(id, action).then(() => ({ id, action })),
    onSuccess: ({ id, action }) => {
      queryClient.setQueryData<PaginatedResult<TradeInDto>>(
        [...tradeInQueryKeys.mine(), { page }],
        (current) =>
          current
            ? {
                ...current,
                items: current.items.map((item) =>
                  item.id === id
                    ? {
                        ...item,
                        status: action === 'accept' ? 'accepted' : 'declined',
                        statusLabel: action === 'accept' ? 'Offre acceptée' : 'Offre refusée',
                      }
                    : item,
                ),
              }
            : current,
      );
      setMessage(
        action === 'accept' ? 'Votre accord a été enregistré.' : 'Votre refus a été enregistré.',
      );
    },
    onError: (responseError) => setError(getHttpErrorMessage(responseError)),
  });

  const receiptMutation = useMutation({
    mutationFn: ({ id, reference }: { id: number; reference: string }) =>
      downloadMyTradeInReceipt(id).then((blob) => ({ blob, reference })),
    onSuccess: ({ blob, reference }) =>
      downloadBlob(blob, `justificatif-reprise-${reference}.pdf`),
    onError: (downloadError) => setError(getHttpErrorMessage(downloadError)),
  });

  const respond = async (id: number, action: 'accept' | 'decline') => {
    responseMutation.mutate({ id, action });
  };

  const downloadReceipt = async (id: number, reference: string) => {
    receiptMutation.mutate({ id, reference });
  };

  return (
    <SiteLayout headerVariant="light">
      <PublicPageShell
        title="Mes reprises"
        description="Suivez vos demandes de reprise, les estimations reçues et les justificatifs disponibles."
      >
        {message ? <FeedbackMessage variant="success">{message}</FeedbackMessage> : null}
        {tradeInsQuery.isLoading ? <LoadingState>Chargement de vos demandes…</LoadingState> : null}
        {tradeInsQuery.error || error ? (
          <ErrorState>
            {error ?? getHttpErrorMessage(tradeInsQuery.error, 'Impossible de charger vos reprises.')}
          </ErrorState>
        ) : null}
        {!tradeInsQuery.isLoading && !tradeInsQuery.error && !error && items.length === 0 ? (
          <EmptyState>Aucune demande de reprise pour le moment.</EmptyState>
        ) : null}
        <div className="space-y-4">
          {items.map((item) => (
            <PublicPageSection key={item.id} className="transition hover:border-brand-200">
              <div className="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                <div>
                  <p className="text-xs font-semibold uppercase tracking-[0.24em] text-stone-500">
                    {item.reference}
                  </p>
                  <h2 className="mt-1 text-xl font-semibold text-brand-900">
                    {item.productName}
                  </h2>
                  <p className="mt-1 text-sm text-stone-600">
                    Demande du {formatFrenchDate(item.createdAt) ?? '—'} · {item.statusLabel}
                  </p>
                </div>
                {item.offerCents !== null ? (
                  <strong className="rounded-full bg-brand-50 px-4 py-2 text-brand-900">
                    {formatEuroCents(item.offerCents)}
                  </strong>
                ) : null}
              </div>
              <p className="mt-4 text-stone-700">
                Estimation indicative : {formatEuroCents(item.estimatedMinCents)} à{' '}
                {formatEuroCents(item.estimatedMaxCents)}
              </p>
              {item.adminNote ? (
                <p className="mt-3 rounded-2xl bg-stone-50 p-4 text-sm text-stone-700">
                  Message de Hociatec : {item.adminNote}
                </p>
              ) : null}
              {item.voucherCode ? (
                <p className="mt-3 rounded-2xl bg-emerald-50 p-4 text-sm text-emerald-900">
                  Votre avoir client : <strong className="font-mono">{item.voucherCode}</strong>
                </p>
              ) : null}
              {item.status === 'offer_sent' ? (
                <div className="mt-4 flex flex-wrap gap-3">
                  <button
                    className="inline-flex items-center rounded-full bg-brand-900 px-5 py-2 text-sm font-semibold text-white transition hover:bg-brand-800"
                    type="button"
                    onClick={() => void respond(item.id, 'accept')}
                  >
                    Accepter l’offre
                  </button>
                  <button
                    className="inline-flex items-center rounded-full border border-red-200 px-5 py-2 text-sm font-semibold text-red-600 transition hover:border-red-400"
                    type="button"
                    onClick={() => void respond(item.id, 'decline')}
                  >
                    Refuser
                  </button>
                </div>
              ) : null}
              {item.receiptAvailable ? (
                <button
                  type="button"
                  className="mt-4 inline-flex items-center rounded-full border border-brand-200 px-5 py-2 text-sm font-semibold text-stone-700 transition hover:border-brand-600"
                  onClick={() => void downloadReceipt(item.id, item.reference)}
                >
                  Télécharger mon justificatif PDF
                </button>
              ) : null}
            </PublicPageSection>
          ))}
        </div>
        <PaginationControls
          page={pagination.page}
          total={pagination.total}
          totalLabel="reprise"
          totalPages={pagination.totalPages}
          onPageChange={setPage}
        />
      </PublicPageShell>
    </SiteLayout>
  );
};
