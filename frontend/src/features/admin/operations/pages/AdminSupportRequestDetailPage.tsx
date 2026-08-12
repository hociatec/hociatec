import { useState } from 'react';
import { Link, useParams } from 'react-router';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';

import { PageContainer } from '@/shared/components/layout/PageContainer';
import { EmptyState, ErrorState, FeedbackMessage, LoadingState } from '@/shared/components/ui/page-state';
import { adminOperationsQueryKeys } from '@/features/admin/operations/queryKeys';
import {
  fetchSupportRequestById,
  replySupportRequest,
  updateSupportRequest,
} from '@/features/admin/operations/api';
import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import { formatFrenchDateTime } from '@/shared/lib/formatters';

const statusTone = (status: string) => {
  switch (status) {
    case 'resolved':
      return 'bg-emerald-100 text-emerald-800';
    case 'refused':
      return 'bg-red-100 text-red-700';
    case 'waiting_customer':
      return 'bg-amber-100 text-amber-800';
    case 'in_progress':
      return 'bg-brand-100 text-brand-800';
    default:
      return 'bg-stone-100 text-stone-700';
  }
};

export const AdminSupportRequestDetailPage = () => {
  const params = useParams();
  const supportId = Number(params.supportId ?? '0');
  const queryClient = useQueryClient();
  const [replyForm, setReplyForm] = useState({
    subject: '',
    message: '',
  });
  const [actionMessage, setActionMessage] = useState<string | null>(null);

  const detailQuery = useQuery({
    queryKey: [...adminOperationsQueryKeys.base(), 'support-detail', supportId],
    queryFn: () => fetchSupportRequestById(supportId),
    enabled: Number.isFinite(supportId) && supportId > 0,
  });

  const updateMutation = useMutation({
    mutationFn: (payload: { id: number; status: string }) => updateSupportRequest(payload.id, { status: payload.status }),
    onSuccess: async () => {
      setActionMessage('Statut SAV mis à jour.');
      await queryClient.invalidateQueries({ queryKey: adminOperationsQueryKeys.base() });
    },
  });

  const replyMutation = useMutation({
    mutationFn: (payload: { id: number; subject: string; message: string }) =>
      replySupportRequest(payload.id, {
        subject: payload.subject,
        message: payload.message,
        status: 'waiting_customer',
      }),
    onSuccess: async () => {
      setReplyForm({ subject: '', message: '' });
      setActionMessage('Réponse SAV envoyée au client.');
      await queryClient.invalidateQueries({ queryKey: adminOperationsQueryKeys.base() });
    },
  });

  const item = detailQuery.data ?? null;
  const error = detailQuery.error
    ? getHttpErrorMessage(detailQuery.error, 'Impossible de charger ce dossier SAV.')
    : updateMutation.error
      ? getHttpErrorMessage(updateMutation.error, 'Impossible de mettre à jour le statut.')
      : replyMutation.error
        ? getHttpErrorMessage(replyMutation.error, 'Impossible d’envoyer la réponse SAV.')
        : null;

  return (
    <PageContainer
      size="admin"
      title={item ? `SAV #${item.id}` : 'Dossier SAV'}
      headerActions={<Link className="text-sm underline" to="/admin/customers/support">Retour à la liste</Link>}
    >
      {detailQuery.isLoading ? <LoadingState>Chargement du dossier...</LoadingState> : null}
      {error ? <ErrorState onAction={() => void detailQuery.refetch()}>{error}</ErrorState> : null}
      {actionMessage ? <FeedbackMessage variant="success">{actionMessage}</FeedbackMessage> : null}

      {item ? (
        <div className="space-y-6">
          <section className="rounded-2xl border border-brand-100 bg-white p-5 shadow-sm">
            <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
              <div className="space-y-2">
                <p className="text-sm text-stone-500">Client : {item.customer.name} · {item.customer.email}</p>
                <h2 className="text-2xl font-semibold text-brand-900">{item.subject}</h2>
                <p className="text-sm text-stone-600">
                  Créé le {formatFrenchDateTime(item.createdAt)}
                  {item.order?.number ? ` · Commande ${item.order.number}` : ''}
                </p>
                {item.awaitingReplyLabel ? (
                  <p className="text-sm font-medium text-brand-900">{item.awaitingReplyLabel}</p>
                ) : null}
              </div>
              <div className="space-y-3">
                <div className={`w-fit rounded-full px-3 py-1 text-sm font-semibold ${statusTone(item.status)}`}>
                  {item.statusLabel}
                </div>
                <label className="block">
                  <span className="text-sm font-medium text-stone-700">Changer le statut</span>
                  <select
                    className="mt-1 w-full rounded-xl border border-brand-100 px-3 py-2 text-sm text-brand-900"
                    value={item.status}
                    onChange={(event) => updateMutation.mutate({ id: item.id, status: event.target.value })}
                  >
                    <option value="new">Nouveau</option>
                    <option value="in_progress">En cours</option>
                    <option value="waiting_customer">En attente client</option>
                    <option value="resolved">Résolu</option>
                    <option value="refused">Refusé</option>
                  </select>
                </label>
              </div>
            </div>
          </section>

          <section className="rounded-2xl border border-brand-100 bg-white p-5 shadow-sm">
            <h3 className="text-lg font-semibold text-brand-900">Historique</h3>
            <div className="mt-4 space-y-4">
              {item.timeline.length === 0 ? (
                <EmptyState>Aucun échange enregistré.</EmptyState>
              ) : (
                item.timeline.map((entry) => (
                  <article key={entry.id} className="rounded-2xl border border-brand-100 bg-brand-50 p-4">
                    <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                      <p className="text-sm font-semibold text-brand-900">{entry.authorLabel}</p>
                      <p className="text-xs text-stone-500">{formatFrenchDateTime(entry.createdAt)}</p>
                    </div>
                    {entry.subject ? <p className="mt-2 text-sm font-medium text-brand-900">{entry.subject}</p> : null}
                    {entry.message ? <p className="mt-2 whitespace-pre-wrap text-sm text-stone-700">{entry.message}</p> : null}
                    {entry.statusLabel ? <p className="mt-2 text-xs text-stone-500">Statut : {entry.statusLabel}</p> : null}
                    {(entry.attachments ?? []).length > 0 ? (
                      <div className="mt-3 flex flex-wrap gap-2">
                        {entry.attachments?.map((attachment) => (
                          <a
                            key={attachment.name}
                            className="rounded-full border border-brand-200 px-3 py-1 text-xs font-medium text-brand-700 hover:border-brand-400"
                            href={`/api/admin/operations/support-requests/${item.id}/attachments/${encodeURIComponent(attachment.name)}`}
                            target="_blank"
                            rel="noreferrer"
                          >
                            {attachment.originalName}
                          </a>
                        ))}
                      </div>
                    ) : null}
                  </article>
                ))
              )}
            </div>
          </section>

          <section className="rounded-2xl border border-brand-100 bg-white p-5 shadow-sm">
            <h3 className="text-lg font-semibold text-brand-900">Répondre au client</h3>
            <div className="mt-4 space-y-4">
              <label className="block">
                <span className="text-sm font-medium text-stone-700">Sujet</span>
                <input
                  className="mt-1 w-full rounded-xl border border-brand-100 px-3 py-2 text-sm text-brand-900"
                  value={replyForm.subject}
                  onChange={(event) => setReplyForm((current) => ({ ...current, subject: event.target.value }))}
                  placeholder={`Réponse SAV #${item.id}`}
                />
              </label>
              <label className="block">
                <span className="text-sm font-medium text-stone-700">Message</span>
                <textarea
                  className="mt-1 w-full rounded-xl border border-brand-100 px-3 py-2 text-sm text-brand-900"
                  rows={5}
                  value={replyForm.message}
                  onChange={(event) => setReplyForm((current) => ({ ...current, message: event.target.value }))}
                  placeholder="Réponse envoyée au client"
                />
              </label>
              <button
                type="button"
                className="rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50"
                onClick={() => replyMutation.mutate({ id: item.id, subject: replyForm.subject || `Réponse SAV #${item.id}`, message: replyForm.message })}
                disabled={replyMutation.isPending || replyForm.message.trim() === ''}
              >
                {replyMutation.isPending ? 'Envoi...' : 'Envoyer la réponse'}
              </button>
            </div>
          </section>
        </div>
      ) : null}
    </PageContainer>
  );
};
