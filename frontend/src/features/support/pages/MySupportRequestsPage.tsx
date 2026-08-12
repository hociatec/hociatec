import { useEffect, useMemo, useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';

import { SiteLayout } from '@/shared/components/layout/SiteLayout';
import { BlockingModal } from '@/shared/components/ui/BlockingModal';
import { EmptyState, ErrorState, LoadingState } from '@/shared/components/ui/page-state';
import { PaginationControls } from '@/shared/components/ui/PaginationControls';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import { formatFrenchDateTime } from '@/shared/lib/formatters';
import { fetchMyOrders, type OrderDto } from '@/features/orders/publicApi';
import {
  createMySupportRequest,
  fetchMySupportRequestById,
  fetchMySupportRequests,
  replyMySupportRequest,
} from '@/features/support/api';
import { supportQueryKeys } from '@/features/support/queryKeys';

const supportStatusTone = (status: string) => {
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

export const MySupportRequestsPage = () => {
  useDocumentTitle('Mes demandes SAV');

  const queryClient = useQueryClient();
  const [page, setPage] = useState(1);
  const [selectedSupportId, setSelectedSupportId] = useState<number | null>(null);
  const [createModalOpen, setCreateModalOpen] = useState(false);
  const [createForm, setCreateForm] = useState({
    subject: '',
    reason: 'other',
    message: '',
    orderId: '',
  });
  const [createAttachments, setCreateAttachments] = useState<File[]>([]);
  const [replyForm, setReplyForm] = useState({
    subject: '',
    message: '',
  });
  const [replyAttachments, setReplyAttachments] = useState<File[]>([]);

  const supportQuery = useQuery({
    queryKey: supportQueryKeys.list(page),
    queryFn: () => fetchMySupportRequests(page, 10),
  });

  const ordersQuery = useQuery({
    queryKey: ['orders', 'mine', 'support-select'],
    queryFn: () => fetchMyOrders(1, 50),
  });

  useEffect(() => {
    const items = supportQuery.data?.items ?? [];
    if (items.length === 0) {
      setSelectedSupportId(null);
      return;
    }

    if (selectedSupportId !== null && !items.some((item) => item.id === selectedSupportId)) {
      setSelectedSupportId(null);
    }
  }, [selectedSupportId, supportQuery.data]);

  const detailQuery = useQuery({
    queryKey: supportQueryKeys.detail(selectedSupportId),
    queryFn: () => fetchMySupportRequestById(selectedSupportId as number),
    enabled: selectedSupportId !== null,
  });

  const createMutation = useMutation({
    mutationFn: createMySupportRequest,
    onSuccess: async (created) => {
      setCreateForm({ subject: '', reason: 'other', message: '', orderId: '' });
      setCreateAttachments([]);
      setCreateModalOpen(false);
      setPage(1);
      setSelectedSupportId(created.id);
      await queryClient.invalidateQueries({ queryKey: supportQueryKeys.mine() });
    },
  });

  const replyMutation = useMutation({
    mutationFn: (payload: { supportId: number; subject?: string | null; message: string; attachments?: File[] }) =>
      replyMySupportRequest(payload.supportId, {
        message: payload.message,
        ...(payload.subject !== undefined ? { subject: payload.subject } : {}),
        ...(payload.attachments !== undefined ? { attachments: payload.attachments } : {}),
      }),
    onSuccess: async (updated) => {
      setReplyForm({ subject: '', message: '' });
      setReplyAttachments([]);
      setSelectedSupportId(updated.id);
      await queryClient.invalidateQueries({ queryKey: supportQueryKeys.mine() });
    },
  });

  const selectedSupport = detailQuery.data ?? null;
  const supportItems = supportQuery.data?.items ?? [];
  const pagination = supportQuery.data?.meta ?? { page, perPage: 10, total: 0, totalPages: 1 };
  const orderOptions = useMemo(() => ordersQuery.data?.items ?? [], [ordersQuery.data]);

  const createError = createMutation.error
    ? getHttpErrorMessage(createMutation.error, 'Impossible de créer votre demande SAV.')
    : null;
  const replyError = replyMutation.error
    ? getHttpErrorMessage(replyMutation.error, 'Impossible d’envoyer votre réponse.')
    : null;
  const listError = supportQuery.error
    ? getHttpErrorMessage(supportQuery.error, 'Impossible de charger vos demandes SAV.')
    : null;
  const detailError = detailQuery.error
    ? getHttpErrorMessage(detailQuery.error, 'Impossible de charger le détail de cette demande SAV.')
    : null;

  const handleCreate = () => {
    createMutation.mutate({
      subject: createForm.subject.trim() || 'Demande SAV',
      reason: createForm.reason,
      message: createForm.message.trim(),
      orderId: createForm.orderId ? Number(createForm.orderId) : null,
      attachments: createAttachments,
    });
  };

  const handleReply = () => {
    if (selectedSupportId === null) {
      return;
    }

    replyMutation.mutate({
      supportId: selectedSupportId,
      subject: replyForm.subject.trim() || null,
      message: replyForm.message.trim(),
      attachments: replyAttachments,
    });
  };

  const selectedSupportStatusClass = selectedSupport ? supportStatusTone(selectedSupport.status) : supportStatusTone('new');

  return (
    <SiteLayout>
      <div className="mx-auto max-w-7xl px-4 py-10">
        <header className="mb-8 space-y-3">
          <h1 className="text-3xl font-semibold text-brand-900">Mes demandes SAV</h1>
          <p className="max-w-3xl text-stone-600">
            Déclarez un problème, rattachez-le à une commande si besoin, puis suivez clairement les réponses et l’avancement de votre dossier.
          </p>
        </header>

        <div className="space-y-6">
          <section className="grid gap-6 xl:grid-cols-[360px_minmax(0,1fr)]">
            <div className="space-y-6">
            <section className="rounded-2xl border border-brand-100 bg-white p-5 shadow-sm">
              <h2 className="text-lg font-semibold text-brand-900">Nouvelle demande</h2>
              <p className="mt-2 text-sm text-stone-600">
                Ouvrez une demande SAV dans une fenêtre dédiée, sans quitter votre suivi actuel.
              </p>
              <button
                type="button"
                className="mt-4 w-full rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-700"
                onClick={() => setCreateModalOpen(true)}
              >
                Nouvelle demande SAV
              </button>
            </section>

            <section className="rounded-2xl border border-brand-100 bg-white p-5 shadow-sm">
              <div className="flex items-center justify-between gap-3">
                <h2 className="text-lg font-semibold text-brand-900">Mes dossiers</h2>
                <span className="text-sm text-stone-500">{pagination.total} total</span>
              </div>
              {supportQuery.isLoading ? <LoadingState>Chargement des demandes...</LoadingState> : null}
              {listError ? <ErrorState onAction={() => void supportQuery.refetch()}>{listError}</ErrorState> : null}
              {!supportQuery.isLoading && !listError && supportItems.length === 0 ? (
                <EmptyState>Aucune demande SAV pour le moment.</EmptyState>
              ) : null}
              {supportItems.length > 0 ? (
                <>
                  <div className="mt-4 space-y-3">
                    {supportItems.map((item) => (
                      <button
                        key={item.id}
                        type="button"
                        className={`block w-full rounded-2xl border p-4 text-left transition ${
                          item.id === selectedSupportId
                            ? 'border-brand-500 bg-brand-50'
                            : 'border-brand-100 bg-white hover:border-brand-300'
                        }`}
                        onClick={() => setSelectedSupportId(item.id)}
                      >
                        <p className="text-xs text-stone-500">Dossier #{item.id}</p>
                        <p className="mt-1 font-semibold text-brand-900">{item.subject}</p>
                        <p className="mt-2 text-sm text-stone-600">
                          {item.order?.number ? `Commande ${item.order.number} · ` : ''}
                          Mis à jour le {formatFrenchDateTime(item.updatedAt)}
                        </p>
                        {item.awaitingReplyLabel ? (
                          <p className="mt-2 text-xs font-medium text-brand-800">{item.awaitingReplyLabel}</p>
                        ) : null}
                        <p className="mt-3">
                          <span className={`rounded-full px-3 py-1 text-xs font-semibold ${supportStatusTone(item.status)}`}>
                            {item.statusLabel}
                          </span>
                        </p>
                        <p className="mt-3 text-sm font-medium text-brand-700">Ouvrir le suivi</p>
                      </button>
                    ))}
                  </div>
                  <PaginationControls
                    page={pagination.page}
                    total={pagination.total}
                    totalLabel="demande SAV"
                    totalPages={pagination.totalPages}
                    onPageChange={setPage}
                  />
                </>
              ) : null}
            </section>
            </div>
            <section className="rounded-2xl border border-brand-100 bg-white p-5 shadow-sm">
              <h2 className="text-lg font-semibold text-brand-900">Suivi des dossiers</h2>
              <p className="mt-2 text-sm text-stone-600">
                Sélectionnez un dossier dans la liste pour ouvrir son suivi complet dans une fenêtre dédiée et bloquante.
              </p>
              <div className="mt-6">
                <EmptyState>Aucun dossier n’est affiché ici automatiquement.</EmptyState>
              </div>
            </section>
          </section>
        </div>
      </div>
      {createModalOpen ? (
        <BlockingModal
          labelledBy="support-create-modal-title"
          describedBy="support-create-modal-description"
          {...(createMutation.isPending ? {} : { onClose: () => setCreateModalOpen(false) })}
          panelClassName="mx-auto w-full max-w-3xl rounded-2xl border border-brand-100 bg-white p-6 shadow-2xl"
        >
          <div className="space-y-5">
            <div className="flex items-start justify-between gap-4">
              <div>
                <h2 id="support-create-modal-title" className="text-2xl font-semibold text-brand-900">
                  Nouvelle demande SAV
                </h2>
                <p id="support-create-modal-description" className="mt-2 text-sm text-stone-600">
                  Décrivez votre problème, rattachez-le à une commande si nécessaire, puis envoyez votre demande.
                </p>
              </div>
              <button
                type="button"
                className="rounded-full border border-brand-100 px-3 py-1 text-sm text-stone-600 transition hover:border-brand-300 hover:text-brand-900 disabled:cursor-not-allowed disabled:opacity-50"
                onClick={() => setCreateModalOpen(false)}
                disabled={createMutation.isPending}
              >
                Fermer
              </button>
            </div>

            <div className="grid gap-4 md:grid-cols-2">
              <label className="block">
                <span className="text-sm font-medium text-stone-700">Sujet</span>
                <input
                  className="mt-1 w-full rounded-xl border border-brand-100 px-3 py-2 text-sm text-brand-900 outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-100"
                  value={createForm.subject}
                  onChange={(event) => setCreateForm((current) => ({ ...current, subject: event.target.value }))}
                  placeholder="Ex. Produit reçu endommagé"
                />
              </label>
              <label className="block">
                <span className="text-sm font-medium text-stone-700">Commande concernée</span>
                <select
                  className="mt-1 w-full rounded-xl border border-brand-100 px-3 py-2 text-sm text-brand-900 outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-100"
                  value={createForm.orderId}
                  onChange={(event) => setCreateForm((current) => ({ ...current, orderId: event.target.value }))}
                >
                  <option value="">Aucune commande précise</option>
                  {orderOptions.map((order: OrderDto) => (
                    <option key={order.id} value={order.id}>
                      {order.number}
                    </option>
                  ))}
                </select>
              </label>
            </div>

            <label className="block">
              <span className="text-sm font-medium text-stone-700">Motif</span>
              <select
                className="mt-1 w-full rounded-xl border border-brand-100 px-3 py-2 text-sm text-brand-900 outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-100"
                value={createForm.reason}
                onChange={(event) => setCreateForm((current) => ({ ...current, reason: event.target.value }))}
              >
                <option value="defective_product">Produit défectueux</option>
                <option value="wrong_order">Erreur de commande</option>
                <option value="return">Retour</option>
                <option value="exchange">Échange</option>
                <option value="refund">Remboursement</option>
                <option value="other">Autre</option>
              </select>
            </label>

            <label className="block">
              <span className="text-sm font-medium text-stone-700">Votre message</span>
              <textarea
                className="mt-1 w-full rounded-xl border border-brand-100 px-3 py-2 text-sm text-brand-900 outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-100"
                rows={6}
                value={createForm.message}
                onChange={(event) => setCreateForm((current) => ({ ...current, message: event.target.value }))}
                placeholder="Décrivez précisément le problème, ce que vous avez constaté et ce que vous attendez."
              />
            </label>

            <label className="block">
              <span className="text-sm font-medium text-stone-700">Pièces jointes</span>
              <input
                className="mt-1 block w-full text-sm text-stone-700"
                type="file"
                accept=".pdf,image/png,image/jpeg,image/webp"
                multiple
                onChange={(event) => setCreateAttachments(Array.from(event.target.files ?? []))}
              />
              <span className="mt-1 block text-xs text-stone-500">PDF, JPG, PNG, WEBP. 5 fichiers maximum.</span>
            </label>

            {createAttachments.length > 0 ? (
              <div className="rounded-xl border border-brand-100 bg-brand-50 p-3 text-sm text-stone-700">
                {createAttachments.map((file) => file.name).join(' · ')}
              </div>
            ) : null}
            {createError ? <p className="text-sm text-red-600">{createError}</p> : null}

            <div className="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
              <button
                type="button"
                className="rounded-xl border border-brand-100 px-4 py-2 text-sm font-semibold text-stone-700 transition hover:border-brand-300 hover:text-brand-900 disabled:cursor-not-allowed disabled:opacity-50"
                onClick={() => setCreateModalOpen(false)}
                disabled={createMutation.isPending}
              >
                Annuler
              </button>
              <button
                type="button"
                className="rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-700 disabled:cursor-not-allowed disabled:opacity-50"
                onClick={handleCreate}
                disabled={createMutation.isPending || createForm.message.trim() === ''}
              >
                {createMutation.isPending ? 'Envoi en cours...' : 'Créer la demande SAV'}
              </button>
            </div>
          </div>
        </BlockingModal>
      ) : null}
      {selectedSupportId !== null ? (
        <BlockingModal
          labelledBy="support-detail-modal-title"
          describedBy="support-detail-modal-description"
          {...(replyMutation.isPending ? {} : { onClose: () => setSelectedSupportId(null) })}
          panelClassName="mx-auto w-full max-w-5xl rounded-2xl border border-brand-100 bg-white p-6 shadow-2xl"
        >
          <div className="space-y-6">
            <div className="flex items-start justify-between gap-4 border-b border-brand-100 pb-5">
              <div>
                <h2 id="support-detail-modal-title" className="text-2xl font-semibold text-brand-900">
                  Suivi du dossier SAV
                </h2>
                <p id="support-detail-modal-description" className="mt-2 text-sm text-stone-600">
                  Consultez l’historique complet et répondez directement sans quitter la page.
                </p>
              </div>
              <button
                type="button"
                className="rounded-full border border-brand-100 px-3 py-1 text-sm text-stone-600 transition hover:border-brand-300 hover:text-brand-900 disabled:cursor-not-allowed disabled:opacity-50"
                onClick={() => setSelectedSupportId(null)}
                disabled={replyMutation.isPending}
              >
                Fermer
              </button>
            </div>

            {detailQuery.isLoading ? <LoadingState>Chargement du suivi SAV...</LoadingState> : null}
            {detailError ? <ErrorState onAction={() => void detailQuery.refetch()}>{detailError}</ErrorState> : null}

            {selectedSupport ? (
              <div className="space-y-6">
                <header className="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                  <div>
                    <p className="text-sm text-stone-500">Dossier #{selectedSupport.id}</p>
                    <h3 className="mt-1 text-2xl font-semibold text-brand-900">{selectedSupport.subject}</h3>
                    <p className="mt-2 text-sm text-stone-600">
                      Créé le {formatFrenchDateTime(selectedSupport.createdAt)}
                      {selectedSupport.order?.number ? ` · Commande ${selectedSupport.order.number}` : ''}
                    </p>
                  </div>
                  <div className={`w-fit rounded-full px-3 py-1 text-sm font-semibold ${selectedSupportStatusClass}`}>
                    {selectedSupport.statusLabel}
                  </div>
                </header>

                {selectedSupport.awaitingReplyLabel ? (
                  <div className="rounded-2xl border border-brand-100 bg-brand-50 px-4 py-3 text-sm font-medium text-brand-900">
                    {selectedSupport.awaitingReplyLabel}
                  </div>
                ) : null}

                <div className="space-y-4">
                  <h3 className="text-lg font-semibold text-brand-900">Historique du dossier</h3>
                  {selectedSupport.timeline.length === 0 ? (
                    <EmptyState>Aucun message enregistré pour cette demande.</EmptyState>
                  ) : (
                    <div className="space-y-4">
                      {selectedSupport.timeline.map((entry) => (
                        <article key={entry.id} className="rounded-2xl border border-brand-100 bg-brand-50 p-4">
                          <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <p className="text-sm font-semibold text-brand-900">{entry.authorLabel}</p>
                            <p className="text-xs text-stone-500">{formatFrenchDateTime(entry.createdAt)}</p>
                          </div>
                          {entry.subject ? <p className="mt-2 text-sm font-medium text-brand-900">{entry.subject}</p> : null}
                          {entry.message ? <p className="mt-2 whitespace-pre-wrap text-sm text-stone-700">{entry.message}</p> : null}
                          {entry.statusLabel ? (
                            <p className="mt-2 text-xs text-stone-500">
                              Statut : <span className="font-medium text-stone-700">{entry.statusLabel}</span>
                            </p>
                          ) : null}
                          {(entry.attachments ?? []).length > 0 ? (
                            <div className="mt-3 flex flex-wrap gap-2">
                              {entry.attachments?.map((attachment) => (
                                <a
                                  key={attachment.name}
                                  className="rounded-full border border-brand-200 px-3 py-1 text-xs font-medium text-brand-700 hover:border-brand-400"
                                  href={`/api/support/me/${selectedSupport.id}/attachments/${encodeURIComponent(attachment.name)}`}
                                  target="_blank"
                                  rel="noopener noreferrer"
                                >
                                  {attachment.originalName}
                                </a>
                              ))}
                            </div>
                          ) : null}
                        </article>
                      ))}
                    </div>
                  )}
                </div>

                <div className="rounded-2xl border border-brand-100 bg-white p-4">
                  <h3 className="text-lg font-semibold text-brand-900">Répondre sur ce dossier</h3>
                  <p className="mt-1 text-sm text-stone-600">
                    Ajoutez un complément d’information ou répondez à l’équipe Hociatec directement dans le suivi.
                  </p>
                  <div className="mt-4 space-y-4">
                    <label className="block">
                      <span className="text-sm font-medium text-stone-700">Sujet de votre réponse</span>
                      <input
                        className="mt-1 w-full rounded-xl border border-brand-100 px-3 py-2 text-sm text-brand-900 outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-100"
                        value={replyForm.subject}
                        onChange={(event) => setReplyForm((current) => ({ ...current, subject: event.target.value }))}
                        placeholder="Optionnel"
                      />
                    </label>
                    <label className="block">
                      <span className="text-sm font-medium text-stone-700">Message</span>
                      <textarea
                        className="mt-1 w-full rounded-xl border border-brand-100 px-3 py-2 text-sm text-brand-900 outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-100"
                        rows={5}
                        value={replyForm.message}
                        onChange={(event) => setReplyForm((current) => ({ ...current, message: event.target.value }))}
                        placeholder="Ajoutez ici les précisions, photos envoyées séparément, numéro de série, tests déjà réalisés, etc."
                      />
                    </label>
                    <label className="block">
                      <span className="text-sm font-medium text-stone-700">Pièces jointes</span>
                      <input
                        className="mt-1 block w-full text-sm text-stone-700"
                        type="file"
                        accept=".pdf,image/png,image/jpeg,image/webp"
                        multiple
                        onChange={(event) => setReplyAttachments(Array.from(event.target.files ?? []))}
                      />
                    </label>
                    {replyAttachments.length > 0 ? (
                      <div className="rounded-xl border border-brand-100 bg-brand-50 p-3 text-sm text-stone-700">
                        {replyAttachments.map((file) => file.name).join(' · ')}
                      </div>
                    ) : null}
                    {replyError ? <p className="text-sm text-red-600">{replyError}</p> : null}
                    <div className="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                      <button
                        type="button"
                        className="rounded-xl border border-brand-100 px-4 py-2 text-sm font-semibold text-stone-700 transition hover:border-brand-300 hover:text-brand-900 disabled:cursor-not-allowed disabled:opacity-50"
                        onClick={() => setSelectedSupportId(null)}
                        disabled={replyMutation.isPending}
                      >
                        Fermer
                      </button>
                      <button
                        type="button"
                        className="rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-700 disabled:cursor-not-allowed disabled:opacity-50"
                        onClick={handleReply}
                        disabled={replyMutation.isPending || replyForm.message.trim() === ''}
                      >
                        {replyMutation.isPending ? 'Envoi en cours...' : 'Envoyer ma réponse'}
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            ) : null}
          </div>
        </BlockingModal>
      ) : null}
    </SiteLayout>
  );
};
