import { useEffect, useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import {
  fetchAdminCampaigns,
  createAdminCampaign,
  updateAdminCampaign,
  deleteAdminCampaign,
  fetchBugReportComments,
  createBugReportComment,
  type AdminCampaignDto,
} from '../api';
import { PageContainer } from '@/shared/components/PageContainer';
import { useToast } from '@/shared/components/ui/toast';
import { useConfirm } from '@/shared/components/ui/confirm';
import { Edit, Eye, MessageSquare, Plus, Trash2, X } from 'lucide-react';
import {
  Dialog,
  DialogBackdrop,
  DialogDescription,
  DialogPanel,
  DialogTitle,
} from '@/shared/components/ui/dialog';
import {
  bugReportStatusLabels,
  campaignStateLabels,
  formatBetaLabel,
  formatDate,
  severityLabels,
} from '@/features/betaTest/lib/betaLabels';

const formatDateInput = (date: Date) => date.toISOString().slice(0, 10);

const defaultCampaignDates = () => {
  const startsAt = new Date();
  const endsAt = new Date(startsAt);
  endsAt.setDate(startsAt.getDate() + 30);

  return {
    startsAt: formatDateInput(startsAt),
    endsAt: formatDateInput(endsAt),
  };
};

const emptyCampaignForm = () => ({
  name: '',
  description: '',
  status: 'draft',
  ...defaultCampaignDates(),
});

const REPORTS_PER_PAGE = 6;

export const AdminBetaCampaignsPage = () => {
  const queryClient = useQueryClient();
  const toast = useToast();
  const confirm = useConfirm();

  // Modals visibility & data state
  const [isAddOpen, setIsAddOpen] = useState(false);
  const [isEditOpen, setIsEditOpen] = useState(false);
  const [isDetailOpen, setIsDetailOpen] = useState(false);
  const [selectedCampaign, setSelectedCampaign] = useState<AdminCampaignDto | null>(null);
  const [selectedReportId, setSelectedReportId] = useState<number | null>(null);
  const [newCommentText, setNewCommentText] = useState('');
  const [reportsPage, setReportsPage] = useState(1);

  // Forms states
  const [addForm, setAddForm] = useState(emptyCampaignForm);
  const [editForm, setEditForm] = useState({ name: '', description: '', status: 'draft', startsAt: '', endsAt: '' });

  // Query campaigns list
  const { data: campaigns = [], isLoading, error } = useQuery({
    queryKey: ['adminCampaigns'],
    queryFn: fetchAdminCampaigns,
  });

  const { data: comments = [], isLoading: loadingComments } = useQuery({
    queryKey: ['bugReportComments', selectedReportId],
    queryFn: () => fetchBugReportComments(selectedReportId!),
    enabled: selectedReportId !== null,
  });

  // Mutations
  const createMutation = useMutation({
    mutationFn: (payload: typeof addForm) => createAdminCampaign(payload),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['adminCampaigns'] });
      toast.show('La campagne bêta a été créée avec succès.', { variant: 'success' });
      setIsAddOpen(false);
      setAddForm(emptyCampaignForm());
    },
    onError: (err) => {
      toast.show(err instanceof Error ? err.message : 'Erreur lors de la création.', { variant: 'error' });
    },
  });

  const updateMutation = useMutation({
    mutationFn: ({ id, payload }: { id: number; payload: typeof editForm }) =>
      updateAdminCampaign(id, payload),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['adminCampaigns'] });
      toast.show('La campagne bêta a été mise à jour.', { variant: 'success' });
      setIsEditOpen(false);
      setSelectedCampaign(null);
    },
    onError: (err) => {
      toast.show(err instanceof Error ? err.message : 'Erreur lors de la modification.', { variant: 'error' });
    },
  });

  const deleteMutation = useMutation({
    mutationFn: (id: number) => deleteAdminCampaign(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['adminCampaigns'] });
      toast.show('La campagne bêta a été supprimée.', { variant: 'success' });
    },
    onError: (err) => {
      toast.show(err instanceof Error ? err.message : 'Erreur lors de la suppression.', { variant: 'error' });
    },
  });

  const postCommentMutation = useMutation({
    mutationFn: () => createBugReportComment(selectedReportId!, newCommentText),
    onSuccess: () => {
      setNewCommentText('');
      queryClient.invalidateQueries({ queryKey: ['bugReportComments', selectedReportId] });
      toast.show('Message envoyé.', { variant: 'success' });
    },
    onError: (err) => {
      toast.show(err instanceof Error ? err.message : "Erreur lors de l'envoi du message.", { variant: 'error' });
    },
  });

  // Handlers
  const handleAddSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!addForm.name.trim() || !addForm.description.trim()) {
      toast.show('Le nom et la description sont obligatoires.', { variant: 'error' });
      return;
    }
    createMutation.mutate(addForm);
  };

  const handleEditSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!selectedCampaign) return;
    if (!editForm.name.trim() || !editForm.description.trim()) {
      toast.show('Le nom et la description sont obligatoires.', { variant: 'error' });
      return;
    }
    updateMutation.mutate({ id: selectedCampaign.id, payload: editForm });
  };

  const handleDelete = async (campaign: AdminCampaignDto) => {
    if (
      await confirm({
        title: 'Supprimer la campagne',
        description: `Êtes-vous sûr de vouloir supprimer définitivement la campagne "${campaign.name}" ?`,
        confirmLabel: 'Supprimer',
        cancelLabel: 'Annuler',
      })
    ) {
      deleteMutation.mutate(campaign.id);
    }
  };

  const openEdit = (campaign: AdminCampaignDto) => {
    setSelectedCampaign(campaign);
    setEditForm({
      name: campaign.name,
      description: campaign.description,
      status: campaign.status,
      startsAt: campaign.startsAt?.slice(0, 10) ?? '',
      endsAt: campaign.endsAt?.slice(0, 10) ?? '',
    });
    setIsEditOpen(true);
  };

  const openDetail = (campaign: AdminCampaignDto) => {
    setSelectedCampaign(campaign);
    setReportsPage(1);
    setSelectedReportId(null);
    setIsDetailOpen(true);
  };

  const handlePostComment = (e: React.FormEvent) => {
    e.preventDefault();
    if (!newCommentText.trim()) return;
    postCommentMutation.mutate();
  };

  const selectedCampaignReports = selectedCampaign?.reports ?? [];
  const reportsPageCount = Math.max(1, Math.ceil(selectedCampaignReports.length / REPORTS_PER_PAGE));
  const visibleCampaignReports = selectedCampaignReports.slice(
    (reportsPage - 1) * REPORTS_PER_PAGE,
    reportsPage * REPORTS_PER_PAGE,
  );
  const selectedReport = selectedCampaignReports.find((report) => report.id === selectedReportId);

  useEffect(() => {
    if (!isDetailOpen) return;

    const preventEscape = (event: KeyboardEvent) => {
      if (event.key === 'Escape') {
        event.preventDefault();
        event.stopPropagation();
      }
    };

    document.addEventListener('keydown', preventEscape, true);

    return () => document.removeEventListener('keydown', preventEscape, true);
  }, [isDetailOpen]);

  return (
    <PageContainer size="admin" title="Gestion des campagnes bêta">
      <header className="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
          <p className="text-stone-500">Planifiez, lancez et gérez les campagnes de tests de l'application.</p>
        </div>
        <button
          type="button"
          onClick={() => setIsAddOpen(true)}
          className="inline-flex items-center gap-2 rounded-lg bg-brand-700 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-800 transition shadow-sm"
        >
          <Plus size={16} />
          <span>Nouvelle campagne</span>
        </button>
      </header>

      {isLoading ? (
        <p className="text-stone-500">Chargement des campagnes...</p>
      ) : error ? (
        <p className="text-red-600">Erreur lors du chargement des campagnes.</p>
      ) : campaigns.length === 0 ? (
        <div className="p-8 text-center bg-white border border-stone-200 rounded-lg text-stone-500">
          Aucune campagne bêta configurée pour le moment.
        </div>
      ) : (
        <div className="overflow-x-auto bg-white border border-stone-200 rounded-lg shadow-sm">
          <table className="w-full text-left border-collapse">
            <thead>
              <tr className="bg-stone-50 border-b border-stone-200 text-sm font-semibold text-stone-600">
                <th className="p-4">Nom de la campagne</th>
                <th className="p-4">Description</th>
                <th className="p-4">Période</th>
                <th className="p-4">Date de création</th>
                <th className="p-4">État</th>
                <th className="p-4">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-stone-200 text-sm">
              {campaigns.map((c) => (
                <tr key={c.id} className="hover:bg-stone-50 transition">
                  <td className="p-4 font-semibold text-stone-900">{c.name}</td>
                  <td className="p-4 text-stone-600 max-w-xs truncate">{c.description}</td>
                  <td className="p-4 text-stone-500">
                    <span className="block">Début : {formatDate(c.startsAt)}</span>
                    <span className="block">Fin : {formatDate(c.endsAt)}</span>
                  </td>
                  <td className="p-4 text-stone-500">
                    {formatDate(c.createdAt)}
                  </td>
                  <td className="p-4">
                    <span
                      className={`inline-flex px-2 py-1 rounded text-xs font-semibold ${
                        c.status === 'active'
                          ? 'bg-green-100 text-green-800'
                          : c.status === 'closed'
                            ? 'bg-red-100 text-red-800'
                            : 'bg-stone-100 text-stone-800'
                      }`}
                    >
                      {formatBetaLabel(c.status, campaignStateLabels)}
                    </span>
                  </td>
                  <td className="p-4">
                    <div className="flex gap-2">
                      <button
                        type="button"
                        onClick={() => openDetail(c)}
                        className="p-1.5 text-stone-600 hover:text-brand-700 bg-stone-50 rounded hover:bg-stone-100 transition"
                        title="Consulter"
                      >
                        <Eye size={16} />
                      </button>
                      <button
                        type="button"
                        onClick={() => openEdit(c)}
                        className="p-1.5 text-stone-600 hover:text-brand-700 bg-stone-50 rounded hover:bg-stone-100 transition"
                        title="Modifier"
                      >
                        <Edit size={16} />
                      </button>
                      <button
                        type="button"
                        onClick={() => handleDelete(c)}
                        className="p-1.5 text-red-600 hover:text-red-800 bg-red-50 rounded hover:bg-red-100 transition"
                        title="Supprimer"
                      >
                        <Trash2 size={16} />
                      </button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}

      {/* Add Campaign Modal */}
      <Dialog open={isAddOpen} onClose={() => setIsAddOpen(false)} className="relative z-50">
        <DialogBackdrop className="fixed inset-0 bg-brand-900/70" />
        <div className="fixed inset-0 flex items-center justify-center p-4">
          <DialogPanel className="w-full max-w-xl rounded-xl border border-brand-100 bg-white p-6 shadow-2xl">
            <header className="flex items-center justify-between border-b border-stone-200 pb-4">
              <div>
                <p className="text-xs font-semibold uppercase tracking-[0.2em] text-brand-700">
                  Campagne bêta
                </p>
                <DialogTitle className="font-bold text-xl text-stone-900 mt-1">
                  Nouvelle campagne bêta
                </DialogTitle>
                <DialogDescription className="text-sm text-stone-500 mt-0.5">
                  Renseignez les détails pour créer une nouvelle campagne de tests.
                </DialogDescription>
              </div>
              <button
                type="button"
                className="p-1 text-stone-400 hover:text-stone-700 rounded-full hover:bg-stone-100 transition"
                onClick={() => setIsAddOpen(false)}
                aria-label="Fermer la fenêtre"
              >
                <X size={20} />
              </button>
            </header>

            <form onSubmit={handleAddSubmit} className="mt-6 space-y-4">
              <div className="space-y-2">
                <label className="block text-sm font-medium text-stone-800">
                  Nom de la campagne *
                </label>
                <input
                  type="text"
                  className="w-full rounded-lg border border-brand-100 px-4 py-3 text-base text-brand-900 shadow-sm outline-none transition focus:border-brand-400 focus:ring-4 focus:ring-brand-100"
                  placeholder="Ex: Refonte du panier"
                  value={addForm.name}
                  onChange={(e) => setAddForm({ ...addForm, name: e.target.value })}
                  required
                />
              </div>
              <div className="space-y-2">
                <label className="block text-sm font-medium text-stone-800">
                  Description *
                </label>
                <textarea
                  className="w-full rounded-lg border border-brand-100 px-4 py-3 text-base text-brand-900 shadow-sm outline-none transition focus:border-brand-400 focus:ring-4 focus:ring-brand-100"
                  placeholder="Objectifs de la campagne, fonctionnalités à tester..."
                  rows={4}
                  value={addForm.description}
                  onChange={(e) => setAddForm({ ...addForm, description: e.target.value })}
                  required
                />
              </div>
              <div className="space-y-2">
                <label className="block text-sm font-medium text-stone-800">
                  État initial
                </label>
                <select
                  className="w-full rounded-lg border border-brand-100 px-4 py-3 text-base text-brand-900 bg-white shadow-sm outline-none transition focus:border-brand-400 focus:ring-4 focus:ring-brand-100"
                  value={addForm.status}
                  onChange={(e) => setAddForm({ ...addForm, status: e.target.value })}
                >
                  <option value="draft">Brouillon</option>
                  <option value="active">Active</option>
                  <option value="closed">Clôturée</option>
                </select>
              </div>
              <div className="grid gap-4 sm:grid-cols-2">
                <div className="space-y-2">
                  <label className="block text-sm font-medium text-stone-800">
                    Date de début
                  </label>
                  <input
                    type="date"
                    className="w-full rounded-lg border border-brand-100 px-4 py-3 text-base text-brand-900 shadow-sm outline-none transition focus:border-brand-400 focus:ring-4 focus:ring-brand-100"
                    value={addForm.startsAt}
                    onChange={(e) => setAddForm({ ...addForm, startsAt: e.target.value })}
                  />
                </div>
                <div className="space-y-2">
                  <label className="block text-sm font-medium text-stone-800">
                    Date de fin
                  </label>
                  <input
                    type="date"
                    className="w-full rounded-lg border border-brand-100 px-4 py-3 text-base text-brand-900 shadow-sm outline-none transition focus:border-brand-400 focus:ring-4 focus:ring-brand-100"
                    value={addForm.endsAt}
                    onChange={(e) => setAddForm({ ...addForm, endsAt: e.target.value })}
                  />
                </div>
              </div>
              <div className="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end mt-6">
                <button
                  type="button"
                  onClick={() => setIsAddOpen(false)}
                  disabled={createMutation.isPending}
                  className="inline-flex items-center justify-center rounded-lg border border-brand-100 px-4 py-3 text-sm font-semibold text-stone-700 transition hover:bg-brand-50 focus:outline-none focus:ring-4 focus:ring-brand-100"
                >
                  Annuler
                </button>
                <button
                  type="submit"
                  disabled={createMutation.isPending}
                  className="inline-flex items-center justify-center rounded-lg bg-brand-600 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700 focus:outline-none focus:ring-4 focus:ring-brand-100 disabled:opacity-50"
                >
                  {createMutation.isPending ? 'Création...' : 'Créer la campagne'}
                </button>
              </div>
            </form>
          </DialogPanel>
        </div>
      </Dialog>

      {/* Edit Campaign Modal */}
      {selectedCampaign && (
        <Dialog open={isEditOpen} onClose={() => setIsEditOpen(false)} className="relative z-50">
          <DialogBackdrop className="fixed inset-0 bg-brand-900/70" />
          <div className="fixed inset-0 flex items-center justify-center p-4">
            <DialogPanel className="w-full max-w-xl rounded-xl border border-brand-100 bg-white p-6 shadow-2xl">
              <header className="flex items-center justify-between border-b border-stone-200 pb-4">
                <div>
                  <p className="text-xs font-semibold uppercase tracking-[0.2em] text-brand-700">
                    Campagne bêta
                  </p>
                  <DialogTitle className="font-bold text-xl text-stone-900 mt-1">
                    Modifier la campagne bêta
                  </DialogTitle>
                  <DialogDescription className="text-sm text-stone-500 mt-0.5">
                    Modifiez le nom, la description, l’état ou les dates de la campagne.
                  </DialogDescription>
                </div>
                <button
                  type="button"
                  className="p-1 text-stone-400 hover:text-stone-700 rounded-full hover:bg-stone-100 transition"
                  onClick={() => setIsEditOpen(false)}
                  aria-label="Fermer la fenêtre"
                >
                  <X size={20} />
                </button>
              </header>

              <form onSubmit={handleEditSubmit} className="mt-6 space-y-4">
                <div className="space-y-2">
                  <label className="block text-sm font-medium text-stone-800">
                    Nom de la campagne *
                  </label>
                  <input
                    type="text"
                    className="w-full rounded-lg border border-brand-100 px-4 py-3 text-base text-brand-900 shadow-sm outline-none transition focus:border-brand-400 focus:ring-4 focus:ring-brand-100"
                    value={editForm.name}
                    onChange={(e) => setEditForm({ ...editForm, name: e.target.value })}
                    required
                  />
                </div>
                <div className="space-y-2">
                  <label className="block text-sm font-medium text-stone-800">
                    Description *
                  </label>
                  <textarea
                    className="w-full rounded-lg border border-brand-100 px-4 py-3 text-base text-brand-900 shadow-sm outline-none transition focus:border-brand-400 focus:ring-4 focus:ring-brand-100"
                    rows={4}
                    value={editForm.description}
                    onChange={(e) => setEditForm({ ...editForm, description: e.target.value })}
                    required
                  />
                </div>
                <div className="space-y-2">
                  <label className="block text-sm font-medium text-stone-800">
                    État
                  </label>
                  <select
                    className="w-full rounded-lg border border-brand-100 px-4 py-3 text-base text-brand-900 bg-white shadow-sm outline-none transition focus:border-brand-400 focus:ring-4 focus:ring-brand-100"
                    value={editForm.status}
                    onChange={(e) => setEditForm({ ...editForm, status: e.target.value })}
                  >
                    <option value="draft">Brouillon</option>
                    <option value="active">Active</option>
                    <option value="closed">Clôturée</option>
                  </select>
                </div>
                <div className="grid gap-4 sm:grid-cols-2">
                  <div className="space-y-2">
                    <label className="block text-sm font-medium text-stone-800">
                      Date de début
                    </label>
                    <input
                      type="date"
                      className="w-full rounded-lg border border-brand-100 px-4 py-3 text-base text-brand-900 shadow-sm outline-none transition focus:border-brand-400 focus:ring-4 focus:ring-brand-100"
                      value={editForm.startsAt}
                      onChange={(e) => setEditForm({ ...editForm, startsAt: e.target.value })}
                    />
                  </div>
                  <div className="space-y-2">
                    <label className="block text-sm font-medium text-stone-800">
                      Date de fin
                    </label>
                    <input
                      type="date"
                      className="w-full rounded-lg border border-brand-100 px-4 py-3 text-base text-brand-900 shadow-sm outline-none transition focus:border-brand-400 focus:ring-4 focus:ring-brand-100"
                      value={editForm.endsAt}
                      onChange={(e) => setEditForm({ ...editForm, endsAt: e.target.value })}
                    />
                  </div>
                </div>
                <div className="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end mt-6">
                  <button
                    type="button"
                    onClick={() => setIsEditOpen(false)}
                    disabled={updateMutation.isPending}
                    className="inline-flex items-center justify-center rounded-lg border border-brand-100 px-4 py-3 text-sm font-semibold text-stone-700 transition hover:bg-brand-50 focus:outline-none focus:ring-4 focus:ring-brand-100"
                  >
                    Annuler
                  </button>
                  <button
                    type="submit"
                    disabled={updateMutation.isPending}
                    className="inline-flex items-center justify-center rounded-lg bg-brand-600 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700 focus:outline-none focus:ring-4 focus:ring-brand-100 disabled:opacity-50"
                  >
                    {updateMutation.isPending ? 'Enregistrement...' : 'Enregistrer'}
                  </button>
                </div>
              </form>
            </DialogPanel>
          </div>
        </Dialog>
      )}

      {/* Consult Campaign Modal */}
      {selectedCampaign && (
        <Dialog open={isDetailOpen} onClose={() => undefined} className="relative z-50">
          <DialogBackdrop className="fixed inset-0 bg-brand-900/70" />
          <div
            className="fixed inset-0 flex items-center justify-center p-4"
            onKeyDownCapture={(event) => {
              if (event.key === 'Escape') {
                event.preventDefault();
                event.stopPropagation();
              }
            }}
          >
            <DialogPanel className="max-h-[90vh] w-full max-w-4xl overflow-y-auto rounded-xl border border-brand-100 bg-white p-6 shadow-2xl">
              <header className="border-b border-stone-200 pb-4">
                <button
                  type="button"
                  onClick={() => setIsDetailOpen(false)}
                  className="mb-4 rounded-lg border border-brand-100 px-4 py-2 text-sm font-semibold text-stone-700 transition hover:bg-brand-50 focus:outline-none focus:ring-4 focus:ring-brand-100"
                >
                  Fermer
                </button>
                <DialogTitle className="font-bold text-xl text-stone-900">
                  {selectedCampaign.name}
                </DialogTitle>
              </header>

              <div className="mt-6 space-y-6">
                <section className="grid gap-3 sm:grid-cols-3">
                  <article className="rounded-lg border border-stone-200 bg-stone-50 p-4">
                    <p className="text-sm text-stone-600">Inscrits : <span className="font-semibold text-stone-900">{selectedCampaign.enrolledCount ?? 0}</span></p>
                  </article>
                  <article className="rounded-lg border border-stone-200 bg-stone-50 p-4">
                    <p className="text-sm text-stone-600">Rapports : <span className="font-semibold text-stone-900">{selectedCampaign.reportCount ?? selectedCampaign.reports?.length ?? 0}</span></p>
                  </article>
                  <article className="rounded-lg border border-stone-200 bg-stone-50 p-4">
                    <p className="text-sm text-stone-600">État : <span className="font-semibold text-stone-900">{formatBetaLabel(selectedCampaign.status, campaignStateLabels)}</span></p>
                  </article>
                </section>

                <section className="space-y-3 text-sm text-stone-700">
                  <p className="whitespace-pre-wrap"><span className="font-semibold text-stone-900">Description : </span>{selectedCampaign.description}</p>
                  <p><span className="font-semibold text-stone-900">Date de création : </span>{formatDate(selectedCampaign.createdAt)}</p>
                  <p><span className="font-semibold text-stone-900">Date de début : </span>{formatDate(selectedCampaign.startsAt)}</p>
                  <p><span className="font-semibold text-stone-900">Date de fin : </span>{formatDate(selectedCampaign.endsAt)}</p>
                </section>

                <section className="rounded-lg border border-stone-200">
                  <div className="border-b border-stone-200 px-4 py-3">
                    <h2 className="text-base font-semibold text-stone-900">Rapports liés à la campagne</h2>
                  </div>
                  {selectedCampaignReports.length ? (
                    <div className="divide-y divide-stone-200">
                      {visibleCampaignReports.map((report) => (
                        <article key={report.id} className="p-4">
                          <div className="flex flex-wrap items-start justify-between gap-3">
                            <h3 className="font-semibold text-stone-900">{report.title}</h3>
                            <button
                              type="button"
                              onClick={() => setSelectedReportId(report.id)}
                              className="inline-flex items-center gap-2 rounded-lg border border-brand-100 px-3 py-2 text-sm font-semibold text-brand-700 transition hover:bg-brand-50"
                            >
                              <MessageSquare size={16} />
                              <span>Suivre les échanges</span>
                            </button>
                          </div>
                          <div className="mt-2 grid gap-2 text-sm text-stone-600 sm:grid-cols-2">
                            <p><span className="font-semibold text-stone-900">Auteur : </span>{report.reporter}</p>
                            <p><span className="font-semibold text-stone-900">Date : </span>{formatDate(report.createdAt)}</p>
                            <p><span className="font-semibold text-stone-900">Gravité : </span>{formatBetaLabel(report.severity, severityLabels)}</p>
                            <p><span className="font-semibold text-stone-900">État : </span>{formatBetaLabel(report.status, bugReportStatusLabels)}</p>
                          </div>
                          <p className="mt-3 line-clamp-3 text-sm text-stone-700">{report.description}</p>
                        </article>
                      ))}
                    </div>
                  ) : (
                    <p className="p-4 text-sm text-stone-500">Aucun rapport lié à cette campagne.</p>
                  )}
                  {selectedCampaignReports.length > REPORTS_PER_PAGE ? (
                    <div className="flex flex-wrap items-center justify-between gap-3 border-t border-stone-200 px-4 py-3 text-sm">
                      <p className="text-stone-600">
                        Page {reportsPage} sur {reportsPageCount}
                      </p>
                      <div className="flex gap-2">
                        <button
                          type="button"
                          disabled={reportsPage === 1}
                          onClick={() => setReportsPage((page) => Math.max(1, page - 1))}
                          className="rounded-lg border border-brand-100 px-3 py-2 font-semibold text-stone-700 transition hover:bg-brand-50 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                          Précédent
                        </button>
                        <button
                          type="button"
                          disabled={reportsPage === reportsPageCount}
                          onClick={() => setReportsPage((page) => Math.min(reportsPageCount, page + 1))}
                          className="rounded-lg border border-brand-100 px-3 py-2 font-semibold text-stone-700 transition hover:bg-brand-50 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                          Suivant
                        </button>
                      </div>
                    </div>
                  ) : null}
                </section>

              </div>
            </DialogPanel>
          </div>
        </Dialog>
      )}

      {selectedReport && (
        <Dialog open={Boolean(selectedReport)} onClose={() => setSelectedReportId(null)} className="relative z-[60]">
          <DialogBackdrop className="fixed inset-0 bg-brand-900/75" />
          <div className="fixed inset-0 flex items-center justify-center p-4">
            <DialogPanel className="flex max-h-[85vh] w-full max-w-2xl flex-col rounded-xl border border-brand-100 bg-white shadow-2xl">
              <header className="border-b border-stone-200 p-4">
                <button
                  type="button"
                  onClick={() => setSelectedReportId(null)}
                  className="mb-4 rounded-lg border border-brand-100 px-4 py-2 text-sm font-semibold text-stone-700 transition hover:bg-brand-50 focus:outline-none focus:ring-4 focus:ring-brand-100"
                >
                  Fermer
                </button>
                <DialogTitle className="text-lg font-bold text-stone-900">
                  Suivi des échanges : {selectedReport.title}
                </DialogTitle>
                <p className="mt-1 text-sm text-stone-600">Auteur : {selectedReport.reporter}</p>
              </header>

              <div className="max-h-28 overflow-y-auto border-b border-stone-200 bg-stone-50 p-4 text-sm text-stone-700">
                <p><span className="font-semibold text-stone-900">Description initiale : </span>{selectedReport.description}</p>
              </div>

              <div className="flex-1 space-y-4 overflow-y-auto bg-stone-50/60 p-4">
                {loadingComments ? (
                  <p className="text-center text-sm text-stone-500">Chargement des messages...</p>
                ) : comments.length === 0 ? (
                  <p className="py-4 text-center text-sm text-stone-500">Aucun message pour le moment.</p>
                ) : (
                  comments.map((comment) => {
                    const isAdminMessage = comment.author.role === 'admin';

                    return (
                      <article
                        key={comment.id}
                        className={`max-w-[85%] ${isAdminMessage ? 'ml-auto text-right' : 'mr-auto text-left'}`}
                      >
                        <p className="mb-1 text-xs text-stone-500">
                          {comment.author.firstName} {comment.author.lastName} ({new Date(comment.createdAt).toLocaleString('fr-FR')}) :
                        </p>
                        <div
                          className={`rounded-lg p-3 text-sm ${
                            isAdminMessage
                              ? 'bg-brand-700 text-white'
                              : 'border border-stone-200 bg-white text-stone-800'
                          }`}
                        >
                          <p className="whitespace-pre-wrap">{comment.content}</p>
                        </div>
                      </article>
                    );
                  })
                )}
              </div>

              <form onSubmit={handlePostComment} className="flex gap-2 border-t border-stone-200 p-4">
                <input
                  type="text"
                  placeholder="Rédiger votre réponse..."
                  className="flex-1 rounded-lg border border-stone-300 p-3 text-sm focus:border-brand-700 focus:outline-none"
                  value={newCommentText}
                  onChange={(event) => setNewCommentText(event.target.value)}
                />
                <button
                  type="submit"
                  disabled={postCommentMutation.isPending || !newCommentText.trim()}
                  className="rounded-lg bg-brand-700 px-4 py-3 text-sm font-semibold text-white hover:bg-brand-800 disabled:opacity-50"
                >
                  {postCommentMutation.isPending ? 'Envoi...' : 'Répondre'}
                </button>
              </form>
            </DialogPanel>
          </div>
        </Dialog>
      )}
    </PageContainer>
  );
};
