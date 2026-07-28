import { useState } from 'react';
import { useSearchParams } from 'react-router';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import {
  assignAdminBugReport,
  createBugReportComment,
  deleteAdminBugReport,
  exportAdminBugReports,
  fetchAdminBugReport,
  fetchAdminBugReportDashboard,
  fetchAdminBugReports,
  fetchBugReportActivity,
  fetchBugReportComments,
  markAdminBugReportDuplicate,
  resolveBetaAttachmentUrl,
  updateAdminBugReportStatus,
  type AdminBugReportDto,
} from '../api';
import { useAuth } from '@/features/auth/hooks/useAuth';
import { useConfirm } from '@/shared/components/ui/confirm';
import { useToast } from '@/shared/components/ui/toast';
import { Dialog, DialogBackdrop, DialogPanel, DialogTitle } from '@/shared/components/ui/dialog';
import { Download, MessageSquare, Trash2 } from 'lucide-react';
import { bugReportStatusLabels, formatBetaLabel, formatDate, severityLabels } from '@/features/betaTest/lib/betaLabels';

const terminalStates = new Set(['resolved', 'duplicate', 'rejected']);

const badgeClassName = (value: string) => {
  if (['resolved'].includes(value)) return 'bg-emerald-50 text-emerald-700 ring-emerald-200';
  if (['critical', 'high', 'rejected'].includes(value)) return 'bg-red-50 text-red-700 ring-red-200';
  if (['submitted', 'under_review', 'need_info', 'planned'].includes(value)) return 'bg-amber-50 text-amber-700 ring-amber-200';
  return 'bg-stone-50 text-stone-700 ring-stone-200';
};

export const AdminBugReportsPage = () => {
  const queryClient = useQueryClient();
  const toast = useToast();
  const confirm = useConfirm();
  const { user } = useAuth();
  const [searchParams, setSearchParams] = useSearchParams();
  const [page, setPage] = useState(1);
  const [commentPage, setCommentPage] = useState(1);
  const [statusFilter, setStatusFilter] = useState('');
  const [severityFilter, setSeverityFilter] = useState('');
  const [search, setSearch] = useState('');
  const [assignedFilter, setAssignedFilter] = useState('');
  const [selectedReportId, setSelectedReportId] = useState<number | null>(Number(searchParams.get('reportId') ?? 0) || null);
  const [newCommentText, setNewCommentText] = useState('');
  const [duplicateOfId, setDuplicateOfId] = useState('');
  const [duplicateReason, setDuplicateReason] = useState('');

  const filters = {
    page,
    perPage: 12,
    status: statusFilter || undefined,
    severity: severityFilter || undefined,
    search: search.trim() || undefined,
    assignedTo: assignedFilter || undefined,
  };

  const { data: dashboard } = useQuery({
    queryKey: ['adminBugReportDashboard'],
    queryFn: fetchAdminBugReportDashboard,
  });

  const { data: reportsResult, isLoading, error } = useQuery({
    queryKey: ['adminBugReports', filters],
    queryFn: () => fetchAdminBugReports(filters),
  });

  const reports = reportsResult?.items ?? [];
  const meta = reportsResult?.meta ?? null;
  const { data: selectedReport } = useQuery({
    queryKey: ['adminBugReport', selectedReportId],
    queryFn: () => fetchAdminBugReport(selectedReportId!),
    enabled: selectedReportId !== null,
  });
  const activeReport = reports.find((report) => report.id === selectedReportId) ?? selectedReport;

  const { data: commentsResult, isLoading: loadingComments } = useQuery({
    queryKey: ['bugReportComments', selectedReportId, commentPage],
    queryFn: () => fetchBugReportComments(selectedReportId!, commentPage),
    enabled: selectedReportId !== null,
  });
  const comments = commentsResult?.items ?? [];
  const commentsMeta = commentsResult?.meta ?? null;

  const { data: activities = [] } = useQuery({
    queryKey: ['bugReportActivity', selectedReportId],
    queryFn: () => fetchBugReportActivity(selectedReportId!),
    enabled: selectedReportId !== null,
  });

  const refreshReports = () => {
    queryClient.invalidateQueries({ queryKey: ['adminBugReports'] });
    queryClient.invalidateQueries({ queryKey: ['adminBugReportDashboard'] });
  };

  const updateStatusMutation = useMutation({
    mutationFn: ({ id, status }: { id: number; status: string }) => updateAdminBugReportStatus(id, status),
    onSuccess: () => {
      refreshReports();
      queryClient.invalidateQueries({ queryKey: ['bugReportActivity', selectedReportId] });
      toast.show('État mis à jour.', { variant: 'success' });
    },
    onError: (err) => toast.show(err instanceof Error ? err.message : 'Mise à jour impossible.', { variant: 'error' }),
  });

  const assignMutation = useMutation({
    mutationFn: ({ id, assignedToId }: { id: number; assignedToId?: number | null }) => assignAdminBugReport(id, assignedToId),
    onSuccess: () => {
      refreshReports();
      queryClient.invalidateQueries({ queryKey: ['bugReportActivity', selectedReportId] });
      toast.show('Responsable mis à jour.', { variant: 'success' });
    },
    onError: (err) => toast.show(err instanceof Error ? err.message : 'Assignation impossible.', { variant: 'error' }),
  });

  const duplicateMutation = useMutation({
    mutationFn: ({ id, duplicateOfId: target, reason }: { id: number; duplicateOfId: number; reason?: string }) => markAdminBugReportDuplicate(id, target, reason),
    onSuccess: () => {
      setDuplicateOfId('');
      setDuplicateReason('');
      refreshReports();
      queryClient.invalidateQueries({ queryKey: ['bugReportActivity', selectedReportId] });
      toast.show('Doublon enregistré.', { variant: 'success' });
    },
    onError: (err) => toast.show(err instanceof Error ? err.message : 'Rattachement impossible.', { variant: 'error' }),
  });

  const deleteMutation = useMutation({
    mutationFn: (id: number) => deleteAdminBugReport(id),
    onSuccess: () => {
      refreshReports();
      closeModal();
      toast.show('Signalement supprimé.', { variant: 'success' });
    },
    onError: (err) => toast.show(err instanceof Error ? err.message : 'Suppression impossible.', { variant: 'error' }),
  });

  const postCommentMutation = useMutation({
    mutationFn: () => createBugReportComment(selectedReportId!, newCommentText),
    onSuccess: () => {
      setNewCommentText('');
      queryClient.invalidateQueries({ queryKey: ['bugReportComments', selectedReportId] });
      queryClient.invalidateQueries({ queryKey: ['bugReportActivity', selectedReportId] });
      refreshReports();
      toast.show('Message envoyé.', { variant: 'success' });
    },
    onError: (err) => toast.show(err instanceof Error ? err.message : 'Erreur lors de l’envoi du message.', { variant: 'error' }),
  });

  const openModal = (report: AdminBugReportDto) => {
    setSelectedReportId(report.id);
    setCommentPage(1);
    setSearchParams({ reportId: String(report.id) });
  };

  const closeModal = () => {
    setSelectedReportId(null);
    setCommentPage(1);
    setSearchParams({});
  };

  const resetFilters = () => {
    setPage(1);
    setStatusFilter('');
    setSeverityFilter('');
    setAssignedFilter('');
    setSearch('');
  };

  const useMyReports = () => {
    if (!user?.id) return;
    setAssignedFilter(String(user.id));
    setPage(1);
  };

  const handleDelete = async (id: number) => {
    if (await confirm({
      title: 'Supprimer le signalement',
      description: 'Cette suppression est définitive.',
      confirmLabel: 'Supprimer',
      cancelLabel: 'Annuler',
    })) {
      deleteMutation.mutate(id);
    }
  };

  const activityLabel = (action: string) => ({
    status_changed: 'État modifié',
    assignment_changed: 'Responsable modifié',
    marked_duplicate: 'Doublon rattaché',
    comment_added: 'Message ajouté',
    report_created: 'Signalement créé',
  }[action] ?? action);

  return (
    <div className="admin-page">
      <header className="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold">Gestion des signalements</h1>
          <p className="text-stone-500">Traitement, priorisation, échanges et journal technique du programme bêta.</p>
        </div>
        <button
          type="button"
          onClick={() => exportAdminBugReports(filters)}
          className="inline-flex items-center gap-2 rounded-lg border border-stone-200 bg-white px-4 py-3 text-sm font-semibold text-stone-700 hover:bg-stone-50"
        >
          <Download size={16} />
          Export CSV
        </button>
      </header>

      {dashboard && (
        <section className="mb-6 grid gap-3 md:grid-cols-3 xl:grid-cols-6">
          {[
            ['Signalements ouverts', dashboard.stats.openReports],
            ['Critiques ou hauts', dashboard.stats.criticalOrHigh],
            ['Réponse admin attendue', dashboard.stats.awaitingAdminReply],
            ['Réponse client attendue', dashboard.stats.awaitingUserReply],
            ['Corrigés récemment', dashboard.stats.recentFixed],
            ['Campagnes actives', dashboard.stats.activeCampaigns],
          ].map(([label, value]) => (
            <article key={label} className="rounded-2xl border border-stone-200 bg-white p-4 shadow-sm">
              <p className="text-xs font-semibold uppercase tracking-[0.12em] text-stone-500">{label}</p>
              <p className="mt-2 text-2xl font-bold text-brand-900">{value}</p>
            </article>
          ))}
        </section>
      )}

      <section className="mb-6 rounded-2xl border border-stone-200 bg-stone-50 p-4">
        <div className="grid gap-4 md:grid-cols-5">
          <label className="text-sm font-semibold text-stone-700">
            Recherche
            <input className="mt-1 w-full rounded-lg border border-stone-300 bg-white p-2 font-normal" value={search} onChange={(event) => { setSearch(event.target.value); setPage(1); }} placeholder="Titre, description, email" />
          </label>
          <label className="text-sm font-semibold text-stone-700">
            État
            <select className="mt-1 w-full rounded-lg border border-stone-300 bg-white p-2 font-normal" value={statusFilter} onChange={(event) => { setStatusFilter(event.target.value); setPage(1); }}>
              <option value="">Tous</option>
              {Object.entries(bugReportStatusLabels).map(([value, label]) => <option key={value} value={value}>{label}</option>)}
            </select>
          </label>
          <label className="text-sm font-semibold text-stone-700">
            Gravité
            <select className="mt-1 w-full rounded-lg border border-stone-300 bg-white p-2 font-normal" value={severityFilter} onChange={(event) => { setSeverityFilter(event.target.value); setPage(1); }}>
              <option value="">Toutes</option>
              {Object.entries(severityLabels).map(([value, label]) => <option key={value} value={value}>{label}</option>)}
            </select>
          </label>
          <label className="text-sm font-semibold text-stone-700">
            Responsable
            <select className="mt-1 w-full rounded-lg border border-stone-300 bg-white p-2 font-normal" value={assignedFilter} onChange={(event) => { setAssignedFilter(event.target.value); setPage(1); }}>
              <option value="">Tous</option>
              {dashboard?.admins.map((admin) => <option key={admin.id} value={admin.id}>{admin.name} · {admin.email}</option>)}
            </select>
          </label>
          <div className="flex items-end gap-2">
            <button type="button" onClick={useMyReports} className="rounded-lg border border-brand-100 bg-white px-3 py-2 text-sm font-semibold text-brand-700">Mes signalements</button>
            <button type="button" onClick={resetFilters} className="rounded-lg border border-stone-200 bg-white px-3 py-2 text-sm font-semibold text-stone-700">Réinitialiser</button>
          </div>
        </div>
      </section>

      {isLoading ? (
        <p className="text-stone-500">Chargement des signalements...</p>
      ) : error ? (
        <p className="text-red-600">Erreur lors du chargement des signalements.</p>
      ) : reports.length === 0 ? (
        <p className="rounded-lg border border-stone-200 bg-white p-8 text-center text-stone-500">Aucun signalement ne correspond à ces critères.</p>
      ) : (
        <div className="overflow-x-auto rounded-lg border border-stone-200 bg-white shadow-sm">
          <table className="w-full border-collapse text-left text-sm">
            <thead>
              <tr className="border-b border-stone-200 bg-stone-50 font-semibold text-stone-600">
                <th className="p-4">Signalement</th>
                <th className="p-4">Gravité</th>
                <th className="p-4">État</th>
                <th className="p-4">Responsable</th>
                <th className="p-4">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-stone-200">
              {reports.map((report) => (
                <tr key={report.id} className="hover:bg-stone-50">
                  <td className="p-4">
                    <button type="button" onClick={() => openModal(report)} className="text-left font-bold text-brand-900 underline-offset-4 hover:underline">{report.title}</button>
                    <p className="mt-1 text-xs text-stone-500">Email : {report.reporter} · Date : {formatDate(report.createdAt)}</p>
                    <p className="mt-2 max-w-xl text-stone-700 line-clamp-2">{report.description}</p>
                    {report.duplicateOf && <p className="mt-2 text-xs font-semibold text-amber-700">Rattaché à : {report.duplicateOf.title}</p>}
                  </td>
                  <td className="p-4"><span className={`inline-flex rounded-lg px-3 py-2 text-xs font-semibold ring-1 ${badgeClassName(report.severity)}`}>Gravité : {formatBetaLabel(report.severity, severityLabels)}</span></td>
                  <td className="p-4">
                    <select className="rounded-lg border border-stone-300 bg-white p-2 text-xs" value={report.status} onChange={(event) => updateStatusMutation.mutate({ id: report.id, status: event.target.value })}>
                      {Object.entries(bugReportStatusLabels).map(([value, label]) => <option key={value} value={value}>{label}</option>)}
                    </select>
                  </td>
                  <td className="p-4">
                    <select className="rounded-lg border border-stone-300 bg-white p-2 text-xs" value={report.assignedTo?.id ?? ''} onChange={(event) => assignMutation.mutate({ id: report.id, assignedToId: event.target.value ? Number(event.target.value) : null })}>
                      <option value="">Non assigné</option>
                      {dashboard?.admins.map((admin) => <option key={admin.id} value={admin.id}>{admin.name}</option>)}
                    </select>
                  </td>
                  <td className="p-4">
                    <div className="flex flex-wrap gap-2">
                      <button className="inline-flex items-center gap-1 rounded bg-stone-100 px-3 py-2 text-xs font-semibold text-stone-700 hover:bg-stone-200" onClick={() => openModal(report)}><MessageSquare size={14} /> Suivre</button>
                      <button className="rounded bg-red-50 px-3 py-2 text-xs font-semibold text-red-700 hover:bg-red-100" onClick={() => handleDelete(report.id)}><Trash2 size={14} /></button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}

      {meta && meta.totalPages > 1 && (
        <div className="mt-6 flex items-center justify-center gap-3">
          <button type="button" disabled={page <= 1} onClick={() => setPage((value) => Math.max(1, value - 1))} className="rounded-lg border border-stone-200 px-4 py-2 text-sm font-semibold disabled:opacity-50">Page précédente</button>
          <span className="text-sm text-stone-600">Page {meta.page} sur {meta.totalPages} · {meta.total} signalement{meta.total > 1 ? 's' : ''}</span>
          <button type="button" disabled={page >= meta.totalPages} onClick={() => setPage((value) => Math.min(meta.totalPages, value + 1))} className="rounded-lg border border-stone-200 px-4 py-2 text-sm font-semibold disabled:opacity-50">Page suivante</button>
        </div>
      )}

      {activeReport && (
        <Dialog open={Boolean(activeReport)} onClose={closeModal} className="relative z-50">
          <DialogBackdrop className="fixed inset-0 bg-brand-900/70" />
          <div className="fixed inset-0 flex items-center justify-center p-4">
            <DialogPanel className="flex max-h-[90vh] w-full max-w-5xl flex-col overflow-hidden rounded-xl bg-white shadow-xl">
              <header className="border-b border-stone-200 p-5">
                <button type="button" className="mb-3 rounded-lg border border-stone-200 px-4 py-2 text-sm font-semibold text-stone-700 hover:bg-stone-50" onClick={closeModal}>Fermer</button>
                <DialogTitle className="text-2xl font-bold text-brand-900">{activeReport.title}</DialogTitle>
                <p className="mt-1 text-sm text-stone-500">Email : {activeReport.reporter} · Campagne : {activeReport.campaign || 'Général'} · Date : {formatDate(activeReport.createdAt)}</p>
              </header>

              <div className="grid flex-1 overflow-y-auto md:grid-cols-[1.1fr_0.9fr]">
                <section className="space-y-5 border-r border-stone-200 p-5">
                  <div className="grid gap-3 text-sm sm:grid-cols-2">
                    <p className={`rounded-lg px-3 py-2 ring-1 ${badgeClassName(activeReport.severity)}`}>Gravité : {formatBetaLabel(activeReport.severity, severityLabels)}</p>
                    <p className={`rounded-lg px-3 py-2 ring-1 ${badgeClassName(activeReport.status)}`}>État : {formatBetaLabel(activeReport.status, bugReportStatusLabels)}</p>
                    <p className="rounded-lg bg-stone-50 px-3 py-2 ring-1 ring-stone-200">Responsable : {activeReport.assignedTo?.name ?? 'Non assigné'}</p>
                    <p className="rounded-lg bg-stone-50 px-3 py-2 ring-1 ring-stone-200">Statut de traitement : {terminalStates.has(activeReport.status) ? 'Terminé' : 'Ouvert'}</p>
                  </div>
                  {activeReport.duplicateOf && <p className="rounded-lg bg-amber-50 p-3 text-sm text-amber-800 ring-1 ring-amber-200">Ce signalement est rattaché à : {activeReport.duplicateOf.title}</p>}
                  <section><h2 className="font-semibold text-stone-900">Description</h2><p className="mt-1 whitespace-pre-wrap text-sm leading-6 text-stone-700">{activeReport.description}</p></section>
                  <section><h2 className="font-semibold text-stone-900">Résultat attendu</h2><p className="mt-1 whitespace-pre-wrap text-sm leading-6 text-stone-700">{activeReport.expectedBehavior || 'Non renseigné'}</p></section>
                  <section><h2 className="font-semibold text-stone-900">Résultat constaté</h2><p className="mt-1 whitespace-pre-wrap text-sm leading-6 text-stone-700">{activeReport.actualBehavior || 'Non renseigné'}</p></section>
                  {(activeReport.attachmentUrls ?? []).length > 0 && (
                    <section>
                      <h2 className="font-semibold text-stone-900">Captures</h2>
                      <ul className="mt-2 space-y-1 text-sm">
                        {activeReport.attachmentUrls.map((url, index) => <li key={url}><a className="text-brand-700 underline" href={resolveBetaAttachmentUrl(url)} target="_blank" rel="noreferrer">Ouvrir la capture {index + 1}</a></li>)}
                      </ul>
                    </section>
                  )}
                  <form className="rounded-2xl border border-stone-200 bg-stone-50 p-4" onSubmit={(event) => { event.preventDefault(); if (!duplicateOfId) return; duplicateMutation.mutate({ id: activeReport.id, duplicateOfId: Number(duplicateOfId), reason: duplicateReason }); }}>
                    <h2 className="font-semibold text-stone-900">Rattacher comme doublon</h2>
                    <div className="mt-3 grid gap-3 sm:grid-cols-[1fr_2fr_auto]">
                      <input className="rounded-lg border border-stone-300 bg-white p-2 text-sm" value={duplicateOfId} onChange={(event) => setDuplicateOfId(event.target.value)} inputMode="numeric" placeholder="ID référence" />
                      <input className="rounded-lg border border-stone-300 bg-white p-2 text-sm" value={duplicateReason} onChange={(event) => setDuplicateReason(event.target.value)} placeholder="Raison facultative" />
                      <button className="rounded-lg bg-brand-700 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50" disabled={!duplicateOfId || duplicateMutation.isPending}>Rattacher</button>
                    </div>
                  </form>
                </section>

                <section className="flex min-h-[520px] flex-col">
                  <div className="border-b border-stone-200 p-4">
                    <h2 className="font-semibold text-brand-900">Discussion</h2>
                  </div>
                  <div className="flex-1 space-y-3 overflow-y-auto bg-stone-50 p-4">
                    {loadingComments ? <p className="text-sm text-stone-500">Chargement des messages...</p> : comments.length === 0 ? <p className="text-sm text-stone-500">Aucun message.</p> : comments.map((comment) => {
                      const authorLabel = comment.author.role === 'admin' ? 'Support Hociatec' : comment.author.email;
                      return <p key={comment.id} className="rounded-lg border border-stone-200 bg-white p-3 text-sm"><span className="font-semibold">{authorLabel}</span> <span className="text-stone-500">({new Date(comment.createdAt).toLocaleString()})</span> : {comment.content}</p>;
                    })}
                  </div>
                  {commentsMeta && commentsMeta.totalPages > 1 && (
                    <div className="flex items-center justify-between border-t border-stone-200 bg-white p-3 text-sm">
                      <button type="button" disabled={commentPage <= 1} onClick={() => setCommentPage((value) => Math.max(1, value - 1))} className="rounded border border-stone-200 px-3 py-2 font-semibold disabled:opacity-50">Précédents</button>
                      <span>Page {commentsMeta.page} sur {commentsMeta.totalPages}</span>
                      <button type="button" disabled={commentPage >= commentsMeta.totalPages} onClick={() => setCommentPage((value) => Math.min(commentsMeta.totalPages, value + 1))} className="rounded border border-stone-200 px-3 py-2 font-semibold disabled:opacity-50">Suivants</button>
                    </div>
                  )}
                  <form onSubmit={(event) => { event.preventDefault(); if (newCommentText.trim()) postCommentMutation.mutate(); }} className="flex gap-2 border-t border-stone-200 p-4">
                    <input className="flex-1 rounded-lg border border-stone-300 p-3 text-sm" value={newCommentText} onChange={(event) => setNewCommentText(event.target.value)} placeholder="Rédiger une réponse..." />
                    <button className="rounded-lg bg-brand-700 px-4 py-3 text-sm font-semibold text-white disabled:opacity-50" disabled={postCommentMutation.isPending || !newCommentText.trim()}>{postCommentMutation.isPending ? 'Envoi...' : 'Répondre'}</button>
                  </form>
                  <div className="max-h-48 overflow-y-auto border-t border-stone-200 p-4">
                    <h2 className="font-semibold text-brand-900">Journal technique</h2>
                    <div className="mt-2 space-y-2 text-xs text-stone-600">
                      {activities.length === 0 ? <p>Aucune action journalisée.</p> : activities.map((activity) => <p key={activity.id} className="rounded bg-stone-50 p-2">{activityLabel(activity.action)} · {activity.actor?.email ?? 'Système'} · {new Date(activity.createdAt).toLocaleString()} {activity.fromValue || activity.toValue ? `· ${activity.fromValue ?? 'vide'} → ${activity.toValue ?? 'vide'}` : ''}</p>)}
                    </div>
                  </div>
                </section>
              </div>
            </DialogPanel>
          </div>
        </Dialog>
      )}
    </div>
  );
};
