import { useState } from 'react';
import { Link } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import {
  fetchBetaCampaigns,
  fetchMyBetaProfile,
  fetchMyBugReports,
  fetchBugReportComments,
  createBugReportComment,
} from '../api/betaApi';
import { BetaBugReportDialog } from '../components/BetaBugReportDialog';
import { PageContainer } from '@/shared/components/PageContainer';
import { FeedbackMessage } from '@/shared/components/ui/page-state';
import { MessageSquare, X } from 'lucide-react';
import { useToast } from '@/shared/components/ui/toast';
import { SiteLayout } from '@/shared/components/SiteLayout';
import {
  Dialog,
  DialogBackdrop,
  DialogPanel,
  DialogTitle,
} from '@/shared/components/ui/dialog';

export const BetaDashboardPage = () => {
  const queryClient = useQueryClient();
  const toast = useToast();
  const [selectedReportId, setSelectedReportId] = useState<number | null>(null);
  const [newCommentText, setNewCommentText] = useState('');
  const [isCreateReportOpen, setIsCreateReportOpen] = useState(false);

  const { data: profile, error: profileError } = useQuery<Record<string, unknown>>({
    queryKey: ['betaProfile'],
    queryFn: fetchMyBetaProfile,
  });

  const { data: campaigns = [], error: campaignsError } = useQuery({
    queryKey: ['betaCampaigns'],
    queryFn: fetchBetaCampaigns,
  });

  const { data: reports = [], error: reportsError } = useQuery({
    queryKey: ['betaReports'],
    queryFn: fetchMyBugReports,
  });

  const { data: comments = [], isLoading: loadingComments } = useQuery({
    queryKey: ['myBugReportComments', selectedReportId],
    queryFn: () => fetchBugReportComments(selectedReportId!),
    enabled: selectedReportId !== null,
  });

  const postCommentMutation = useMutation({
    mutationFn: () => createBugReportComment(selectedReportId!, newCommentText),
    onSuccess: () => {
      setNewCommentText('');
      queryClient.invalidateQueries({ queryKey: ['myBugReportComments', selectedReportId] });
      toast.show('Votre message a bien été envoyé.', { variant: 'success' });
    },
    onError: (err) => {
      toast.show(err instanceof Error ? err.message : 'Erreur lors de l\'envoi.', { variant: 'error' });
    },
  });

  const handlePostComment = (e: React.FormEvent) => {
    e.preventDefault();
    if (!newCommentText.trim()) return;
    postCommentMutation.mutate();
  };

  const error = profileError || campaignsError || reportsError;
  const errorMessage = error instanceof Error ? error.message : error ? 'Impossible de charger votre espace bêta.' : null;

  const activeReport = reports.find(r => r.id === selectedReportId);

  const statusLabels: Record<string, string> = {
    submitted: 'Soumis',
    under_review: 'En cours d\'analyse',
    resolved: 'Corrigé',
    closed: 'Fermé',
    rejected: 'Rejeté',
  };

  return (
    <SiteLayout headerVariant="light">
      <PageContainer title="Mon espace bêta">
        <p className="mb-6 text-stone-600">Suivez vos campagnes et vos signalements depuis cet espace.</p>
      {errorMessage && <FeedbackMessage>{errorMessage}</FeedbackMessage>}
      {profile && (
        <section className="mb-8 rounded-lg border border-stone-200 bg-white p-5">
          <h2 className="mb-2 text-xl font-semibold">Votre profil</h2>
          <p>Statut : <strong>{String(profile.status)}</strong></p>
          <Link className="mt-3 inline-flex underline" to="/beta/profile">Modifier mon profil</Link>
        </section>
      )}
      <section className="mb-8">
        <div className="mb-3 flex items-center justify-between">
          <h2 className="text-xl font-semibold">Campagnes disponibles</h2>
          <button
            type="button"
            onClick={() => setIsCreateReportOpen(true)}
            className="inline-flex items-center gap-2 rounded-lg bg-brand-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-800 shadow-sm"
          >
            <span>Nouveau signalement</span>
          </button>
        </div>
        {campaigns.length === 0 ? (
          <p className="text-stone-600">Aucune campagne disponible actuellement.</p>
        ) : (
          <div className="grid gap-4 md:grid-cols-2">
            {campaigns.map(c => (
              <article key={c.id} className="rounded-lg border border-stone-200 bg-white p-5">
                <h3 className="font-semibold">{c.name}</h3>
                <p className="mt-2 text-sm text-stone-600">{c.description}</p>
              </article>
            ))}
          </div>
        )}
      </section>
      <section>
        <h2 className="mb-3 text-xl font-semibold">Mes signalements</h2>
        {reports.length === 0 ? (
          <p className="text-stone-600">Aucun signalement.</p>
        ) : (
          <div className="space-y-3">
            {reports.map(r => (
              <article key={r.id} className="rounded-lg border border-stone-200 bg-white p-4">
                <div className="flex justify-between gap-4">
                  <h3 className="font-semibold">{r.title}</h3>
                  <span className="text-xs font-semibold uppercase text-stone-500">
                    {statusLabels[r.status] || r.status}
                  </span>
                </div>
                <p className="mt-2 text-sm text-stone-600 mb-4">{r.description}</p>
                <div className="flex justify-end border-t border-stone-100 pt-3">
                  <button
                    className="text-xs text-brand-700 hover:text-brand-800 font-semibold flex items-center gap-1.5"
                    onClick={() => setSelectedReportId(r.id)}
                  >
                    <MessageSquare size={14} />
                    <span>Échanger avec l'équipe{r.status === 'resolved' ? ' (Corrigé)' : ''}</span>
                  </button>
                </div>
              </article>
            ))}
          </div>
        )}
      </section>

      {/* Discussion Modal */}
      {selectedReportId !== null && activeReport && (
        <Dialog open={selectedReportId !== null} onClose={() => setSelectedReportId(null)} className="relative z-50">
          <DialogBackdrop className="fixed inset-0 bg-brand-900/70" />
          <div className="fixed inset-0 flex items-center justify-center p-4">
            <DialogPanel className="bg-white rounded-lg max-w-2xl w-full max-h-[85vh] flex flex-col shadow-xl">
              <header className="p-4 border-b border-stone-200 flex items-center justify-between">
                <div>
                  <DialogTitle className="font-bold text-lg">Discussion : {activeReport.title}</DialogTitle>
                  <span className="text-xs text-stone-500">Statut : {statusLabels[activeReport.status] || activeReport.status}</span>
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
                <strong>Votre signalement :</strong>
                <p className="mt-1 whitespace-pre-wrap">{activeReport.description}</p>
              </div>

              {/* Conversation list */}
              <div className="flex-1 overflow-y-auto p-4 space-y-4 bg-stone-50/50">
                {loadingComments ? (
                  <p className="text-center text-stone-500 text-sm">Chargement des messages...</p>
                ) : comments.length === 0 ? (
                  <p className="text-center text-stone-400 text-sm py-4">Pas encore de message. L'équipe technique vous répondra très bientôt ici !</p>
                ) : (
                  comments.map((c) => {
                    const isAdminMsg = c.author.role === 'admin';
                    return (
                      <div
                        key={c.id}
                        className={`flex flex-col max-w-[85%] ${
                          !isAdminMsg ? 'ml-auto items-end' : 'mr-auto items-start'
                        }`}
                      >
                        <span className="text-xs text-stone-500 mb-1">
                          {isAdminMsg ? 'Support Hociatec (Admin)' : 'Vous'}
                        </span>
                        <div
                          className={`p-3 rounded-lg text-sm ${
                            !isAdminMsg
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
                  placeholder="Écrire un message à l'équipe..."
                  className="flex-1 p-3 border border-stone-300 rounded-lg text-sm focus:outline-none focus:border-brand-700"
                  value={newCommentText}
                  onChange={(e) => setNewCommentText(e.target.value)}
                />
                <button
                  type="submit"
                  disabled={postCommentMutation.isPending || !newCommentText.trim()}
                  className="px-4 py-3 bg-brand-700 hover:bg-brand-800 text-white font-semibold rounded-lg text-sm disabled:opacity-50"
                >
                  {postCommentMutation.isPending ? 'Envoi...' : 'Envoyer'}
                </button>
              </form>
            </DialogPanel>
          </div>
        </Dialog>
      )}
      {/* Creation Report Modal */}
      <BetaBugReportDialog open={isCreateReportOpen} onClose={() => setIsCreateReportOpen(false)} />
      </PageContainer>
    </SiteLayout>
  );
};
