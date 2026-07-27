import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import {
  fetchAdminBugReports,
  updateAdminBugReportStatus,
  deleteAdminBugReport,
  fetchBugReportComments,
  createBugReportComment,
} from '../api';
import { useToast } from '@/shared/components/ui/toast';
import { useConfirm } from '@/shared/components/ui/confirm';
import { MessageSquare, Trash2, X } from 'lucide-react';

export const AdminBugReportsPage = () => {
  const queryClient = useQueryClient();
  const toast = useToast();
  const confirm = useConfirm();

  const [selectedReportId, setSelectedReportId] = useState<number | null>(null);
  const [newCommentText, setNewCommentText] = useState('');
  const [statusFilter, setStatusFilter] = useState('');
  const [severityFilter, setSeverityFilter] = useState('');

  const { data: reports = [], isLoading, error } = useQuery({
    queryKey: ['adminBugReports'],
    queryFn: fetchAdminBugReports,
  });

  const { data: comments = [], isLoading: loadingComments } = useQuery({
    queryKey: ['bugReportComments', selectedReportId],
    queryFn: () => fetchBugReportComments(selectedReportId!),
    enabled: selectedReportId !== null,
  });

  const updateStatusMutation = useMutation({
    mutationFn: ({ id, status }: { id: number; status: string }) =>
      updateAdminBugReportStatus(id, status),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['adminBugReports'] });
      toast.show('Statut mis à jour avec succès.', { variant: 'success' });
    },
    onError: (err) => {
      toast.show(err instanceof Error ? err.message : 'Erreur lors de la mise à jour.', { variant: 'error' });
    },
  });

  const deleteMutation = useMutation({
    mutationFn: (id: number) => deleteAdminBugReport(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['adminBugReports'] });
      toast.show('Rapport de bug supprimé.', { variant: 'success' });
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
      toast.show(err instanceof Error ? err.message : 'Erreur lors de l\'envoi du message.', { variant: 'error' });
    },
  });

  const handleDelete = async (id: number) => {
    if (
      await confirm({
        title: 'Supprimer le rapport',
        description: 'Êtes-vous sûr de vouloir supprimer définitivement ce rapport de bug ?',
        confirmLabel: 'Supprimer',
        cancelLabel: 'Annuler',
      })
    ) {
      deleteMutation.mutate(id);
    }
  };

  const handleStatusChange = (id: number, status: string) => {
    updateStatusMutation.mutate({ id, status });
  };

  const handlePostComment = (e: React.FormEvent) => {
    e.preventDefault();
    if (!newCommentText.trim()) return;
    postCommentMutation.mutate();
  };

  const filteredReports = reports.filter((r) => {
    return (
      (statusFilter === '' || r.status === statusFilter) &&
      (severityFilter === '' || r.severity === severityFilter)
    );
  });

  const severityLabels: Record<string, string> = {
    low: 'Faible',
    normal: 'Normale',
    high: 'Haute',
    critical: 'Critique',
  };

  const statusLabels: Record<string, string> = {
    submitted: 'Soumis',
    under_review: 'En cours d\'analyse',
    resolved: 'Corrigé',
    closed: 'Fermé',
    rejected: 'Rejeté',
  };

  const activeReport = reports.find((r) => r.id === selectedReportId);

  return (
    <div className="admin-page">
      <header className="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
          <h1 className="text-2xl font-bold">Gestion des Rapports de Bugs</h1>
          <p className="text-stone-500">Suivez, mettez à jour et communiquez sur les bugs signalés par les bêta-testeurs.</p>
        </div>
      </header>

      {/* Filters */}
      <div className="flex flex-wrap gap-4 p-4 mb-6 rounded-lg bg-stone-50 border border-stone-200">
        <div>
          <label className="block text-xs font-semibold text-stone-600 uppercase mb-1">Filtrer par Statut</label>
          <select
            className="p-2 bg-white border border-stone-300 rounded text-sm min-w-[180px]"
            value={statusFilter}
            onChange={(e) => setStatusFilter(e.target.value)}
          >
            <option value="">Tous les statuts</option>
            {Object.entries(statusLabels).map(([val, label]) => (
              <option key={val} value={val}>
                {label}
              </option>
            ))}
          </select>
        </div>
        <div>
          <label className="block text-xs font-semibold text-stone-600 uppercase mb-1">Filtrer par Gravité</label>
          <select
            className="p-2 bg-white border border-stone-300 rounded text-sm min-w-[180px]"
            value={severityFilter}
            onChange={(e) => setSeverityFilter(e.target.value)}
          >
            <option value="">Toutes les gravités</option>
            {Object.entries(severityLabels).map(([val, label]) => (
              <option key={val} value={val}>
                {label}
              </option>
            ))}
          </select>
        </div>
      </div>

      {isLoading ? (
        <p className="text-stone-500">Chargement des rapports...</p>
      ) : error ? (
        <p className="text-red-600">Erreur lors du chargement des rapports de bug.</p>
      ) : filteredReports.length === 0 ? (
        <p className="p-8 text-center bg-white border border-stone-200 rounded-lg text-stone-500">
          Aucun rapport de bug ne correspond à ces critères.
        </p>
      ) : (
        <div className="overflow-x-auto bg-white border border-stone-200 rounded-lg shadow-sm">
          <table className="w-full text-left border-collapse">
            <thead>
              <tr className="bg-stone-50 border-b border-stone-200 text-sm font-semibold text-stone-600">
                <th className="p-4">Rapport / Utilisateur</th>
                <th className="p-4">Gravité</th>
                <th className="p-4">Campagne</th>
                <th className="p-4">Statut</th>
                <th className="p-4">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-stone-200 text-sm">
              {filteredReports.map((r) => (
                <tr key={r.id} className="hover:bg-stone-50">
                  <td className="p-4">
                    <strong className="block text-stone-900">{r.title}</strong>
                    <span className="block text-xs text-stone-500 mt-1">Signaleur: {r.reporter}</span>
                    <p className="mt-2 text-stone-700 max-w-lg line-clamp-2">{r.description}</p>
                  </td>
                  <td className="p-4">
                    <span
                      className={`inline-flex px-2 py-1 rounded text-xs font-semibold ${
                        r.severity === 'critical'
                          ? 'bg-red-100 text-red-800'
                          : r.severity === 'high'
                            ? 'bg-orange-100 text-orange-800'
                            : 'bg-stone-100 text-stone-800'
                      }`}
                    >
                      {severityLabels[r.severity] || r.severity}
                    </span>
                  </td>
                  <td className="p-4 text-stone-600">{r.campaign || 'Général'}</td>
                  <td className="p-4">
                    <select
                      className="p-1 border border-stone-300 rounded text-xs bg-white"
                      value={r.status}
                      onChange={(e) => handleStatusChange(r.id, e.target.value)}
                    >
                      {Object.entries(statusLabels).map(([val, label]) => (
                        <option key={val} value={val}>
                          {label}
                        </option>
                      ))}
                    </select>
                  </td>
                  <td className="p-4">
                    <div className="flex gap-2">
                      <button
                        className="p-2 text-stone-600 hover:text-brand-700 bg-stone-100 rounded hover:bg-stone-200 flex items-center gap-1"
                        onClick={() => setSelectedReportId(r.id)}
                        title="Échanger"
                      >
                        <MessageSquare size={16} />
                        <span>Discussion</span>
                      </button>
                      <button
                        className="p-2 text-red-600 hover:text-red-800 bg-red-50 rounded hover:bg-red-100"
                        onClick={() => handleDelete(r.id)}
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

      {/* Discussion Modal */}
      {selectedReportId !== null && activeReport && (
        <div className="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
          <div className="bg-white rounded-lg max-w-2xl w-full max-h-[85vh] flex flex-col shadow-xl">
            <header className="p-4 border-b border-stone-200 flex items-center justify-between">
              <div>
                <h2 className="font-bold text-lg">Discussion : {activeReport.title}</h2>
                <span className="text-xs text-stone-500">Par {activeReport.reporter}</span>
              </div>
              <button
                className="p-1 text-stone-500 hover:text-stone-700 rounded-full hover:bg-stone-100"
                onClick={() => setSelectedReportId(null)}
                aria-label="Fermer la discussion"
              >
                <X size={20} />
              </button>
            </header>

            <div className="p-4 bg-stone-50 border-b border-stone-200 max-h-[120px] overflow-y-auto text-sm text-stone-700">
              <strong>Description initiale :</strong>
              <p className="mt-1 whitespace-pre-wrap">{activeReport.description}</p>
            </div>

            {/* Conversation list */}
            <div className="flex-1 overflow-y-auto p-4 space-y-4 bg-stone-50/50">
              {loadingComments ? (
                <p className="text-center text-stone-500 text-sm">Chargement des messages...</p>
              ) : comments.length === 0 ? (
                <p className="text-center text-stone-400 text-sm py-4">Aucun message pour le moment. Engagez la discussion ci-dessous !</p>
              ) : (
                comments.map((c) => {
                  const isAdminMsg = c.author.role === 'admin';
                  return (
                    <div
                      key={c.id}
                      className={`flex flex-col max-w-[85%] ${
                        isAdminMsg ? 'ml-auto items-end' : 'mr-auto items-start'
                      }`}
                    >
                      <span className="text-xs text-stone-500 mb-1">
                        {c.author.firstName} {c.author.lastName} ({isAdminMsg ? 'Admin' : 'Client'})
                      </span>
                      <div
                        className={`p-3 rounded-lg text-sm ${
                          isAdminMsg
                            ? 'bg-brand-700 text-white rounded-br-none'
                            : 'bg-white border border-stone-200 text-stone-800 rounded-bl-none'
                        }`}
                      >
                        <p className="whitespace-pre-wrap">{c.content}</p>
                      </div>
                      <span className="text-[10px] text-stone-400 mt-1">
                        {new Date(c.createdAt).toLocaleString()}
                      </span>
                    </div>
                  );
                })
              )}
            </div>

            {/* Post comment form */}
            <form onSubmit={handlePostComment} className="p-4 border-t border-stone-200 flex gap-2">
              <input
                type="text"
                placeholder="Rédiger votre réponse..."
                className="flex-1 p-3 border border-stone-300 rounded-lg text-sm focus:outline-none focus:border-brand-700"
                value={newCommentText}
                onChange={(e) => setNewCommentText(e.target.value)}
              />
              <button
                type="submit"
                disabled={postCommentMutation.isPending || !newCommentText.trim()}
                className="px-4 py-3 bg-brand-700 hover:bg-brand-800 text-white font-semibold rounded-lg text-sm disabled:opacity-50"
              >
                {postCommentMutation.isPending ? 'Envoi...' : 'Répondre'}
              </button>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};
