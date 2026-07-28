import { useState } from 'react';
import { Link } from 'react-router';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import {
  type BetaCampaign,
  fetchBetaCampaigns,
  fetchMyBetaProfile,
  fetchMyBugReports,
  fetchBugReportComments,
  createBugReportComment,
  resolveBetaAttachmentUrl,
} from '../api/betaApi';
import { BetaBugReportDialog } from '../components/BetaBugReportDialog';
import { PageContainer } from '@/shared/components/PageContainer';
import { FeedbackMessage } from '@/shared/components/ui/page-state';
import { FlaskConical, MessageSquare, Pencil, Plus, ShieldCheck, X } from 'lucide-react';
import { useToast } from '@/shared/components/ui/toast';
import { SiteLayout } from '@/shared/components/SiteLayout';
import {
  Dialog,
  DialogBackdrop,
  DialogPanel,
  DialogTitle,
} from '@/shared/components/ui/dialog';
import {
  betaProfileStatusLabels,
  bugReportStatusLabels,
  campaignStateLabels,
  formatBetaLabel,
  formatDate,
  severityLabels,
} from '../lib/betaLabels';

const badgeClassName = (value: string) => {
  if (['accepted', 'resolved', 'active'].includes(value)) {
    return 'bg-emerald-50 text-emerald-700 ring-emerald-200';
  }

  if (['pending', 'submitted', 'under_review', 'draft'].includes(value)) {
    return 'bg-amber-50 text-amber-700 ring-amber-200';
  }

  if (['rejected', 'closed', 'critical', 'high'].includes(value)) {
    return 'bg-red-50 text-red-700 ring-red-200';
  }

  return 'bg-stone-50 text-stone-700 ring-stone-200';
};

const isCampaignOpenForReports = (campaign: BetaCampaign) => {
  const now = Date.now();
  const startsAt = campaign.startsAt ? new Date(campaign.startsAt).getTime() : null;
  const endsAt = campaign.endsAt ? new Date(campaign.endsAt).getTime() : null;

  return campaign.status === 'active'
    && (startsAt === null || startsAt <= now)
    && (endsAt === null || endsAt >= now);
};

export const BetaDashboardPage = () => {
  const queryClient = useQueryClient();
  const toast = useToast();
  const [selectedReportId, setSelectedReportId] = useState<number | null>(null);
  const [selectedCampaign, setSelectedCampaign] = useState<BetaCampaign | null>(null);
  const [viewedCampaign, setViewedCampaign] = useState<BetaCampaign | null>(null);
  const [newCommentText, setNewCommentText] = useState('');
  const [isCreateReportOpen, setIsCreateReportOpen] = useState(false);

  const {
    data: profile,
    isLoading: isLoadingProfile,
    isError: isProfileError,
  } = useQuery<Record<string, unknown>>({
    queryKey: ['betaProfile'],
    queryFn: fetchMyBetaProfile,
    retry: false,
  });

  const { data: campaigns = [], error: campaignsError } = useQuery({
    queryKey: ['betaCampaigns'],
    queryFn: fetchBetaCampaigns,
    enabled: Boolean(profile),
  });

  const { data: reports = [], error: reportsError } = useQuery({
    queryKey: ['betaReports'],
    queryFn: fetchMyBugReports,
    enabled: Boolean(profile),
  });

  const { data: comments = [], isLoading: loadingComments } = useQuery({
    queryKey: ['myBugReportComments', selectedReportId],
    queryFn: () => fetchBugReportComments(selectedReportId!),
    enabled: selectedReportId !== null && Boolean(profile),
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

  const openCampaignReport = (campaign: BetaCampaign) => {
    setSelectedCampaign(campaign);
    setViewedCampaign(null);
    setIsCreateReportOpen(true);
  };

  const openCampaignDetails = (campaign: BetaCampaign) => {
    setViewedCampaign(campaign);
  };

  if (isLoadingProfile) {
    return (
      <SiteLayout headerVariant="light">
        <PageContainer title="Mon espace bêta">
          <p className="text-stone-500 py-8">Chargement de votre espace bêta...</p>
        </PageContainer>
      </SiteLayout>
    );
  }

  if (isProfileError || !profile) {
    return (
      <SiteLayout headerVariant="light">
        <PageContainer title="Espace Bêta-Testeur">
          <div className="my-8 max-w-2xl mx-auto rounded-2xl border border-brand-100 bg-white p-8 text-center shadow-lg space-y-4">
            <div className="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-brand-50 text-brand-700">
              <FlaskConical size={32} />
            </div>
            <h2 className="text-2xl font-bold text-brand-900">
              Rejoignez le programme Bêta Hociatec
            </h2>
            <p className="text-sm leading-relaxed text-stone-600 max-w-lg mx-auto">
              Votre compte n'est pas encore inscrit au programme de bêta-test. Créez votre profil bêta-testeur pour accéder aux campagnes, tester de nouvelles fonctionnalités et soumettre vos signalements.
            </p>
            <div className="pt-4 flex flex-wrap justify-center gap-3">
              <Link
                to="/beta/profile"
                className="inline-flex items-center justify-center rounded-lg bg-brand-700 px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-800"
              >
                Activer mon profil bêta-testeur
              </Link>
              <Link
                to="/beta-test"
                className="inline-flex items-center justify-center rounded-lg border border-stone-300 bg-white px-6 py-3 text-sm font-semibold text-stone-700 transition hover:bg-stone-50"
              >
                Découvrir le programme
              </Link>
            </div>
          </div>
        </PageContainer>
      </SiteLayout>
    );
  }

  const error = campaignsError || reportsError;
  const errorMessage = error instanceof Error ? error.message : error ? 'Impossible de charger vos données.' : null;

  const activeReport = reports.find((r) => r.id === selectedReportId);
  const profileStatus = typeof profile.status === 'string' ? profile.status : '';
  const canReport = profileStatus === 'accepted';
  const resolvedReports = reports.filter((report) => report.status === 'resolved').length;
  const openReports = reports.filter((report) => !['resolved', 'closed', 'rejected'].includes(report.status)).length;
  const viewedCampaignReports = viewedCampaign
    ? reports.filter((report) => report.campaignId === viewedCampaign.id)
    : [];
  const viewedCampaignCanReport = Boolean(viewedCampaign && canReport && isCampaignOpenForReports(viewedCampaign));

  return (
    <SiteLayout headerVariant="light">
      <PageContainer title="Mon espace bêta">
        {errorMessage && <FeedbackMessage>{errorMessage}</FeedbackMessage>}

        <section className="mb-8 grid gap-4 md:grid-cols-3">
          <article className="rounded-2xl border border-brand-100 bg-white p-5 shadow-sm">
            <p className="text-sm font-medium text-stone-500">Campagnes disponibles</p>
            <p className="mt-2 text-3xl font-bold text-brand-900">{campaigns.length}</p>
          </article>
          <article className="rounded-2xl border border-brand-100 bg-white p-5 shadow-sm">
            <p className="text-sm font-medium text-stone-500">Signalements ouverts</p>
            <p className="mt-2 text-3xl font-bold text-brand-900">{openReports}</p>
          </article>
          <article className="rounded-2xl border border-brand-100 bg-white p-5 shadow-sm">
            <p className="text-sm font-medium text-stone-500">Corrections confirmées</p>
            <p className="mt-2 text-3xl font-bold text-brand-900">{resolvedReports}</p>
          </article>
        </section>

        <section className="mb-8">
          <article className="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm">
            <div className="flex items-start gap-4">
              <div className="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-brand-50 text-brand-700">
                <ShieldCheck size={24} />
              </div>
              <div>
                <h2 className="text-xl font-bold text-brand-900">Votre profil bêta</h2>
                <p className="mt-1 text-sm leading-6 text-stone-600">
                  Votre accès aux campagnes dépend de la validation de ce profil.
                </p>
              </div>
            </div>
            <div className="mt-5 rounded-2xl bg-stone-50 p-4">
              <div className="space-y-2">
                <p id="beta-profile-state-label" className="text-xs font-semibold uppercase tracking-[0.16em] text-stone-500">
                  État actuel
                </p>
                <p>
                  <span
                    className={`inline-flex rounded-full px-3 py-1 text-sm font-bold ring-1 ${badgeClassName(profileStatus)}`}
                    aria-labelledby="beta-profile-state-label"
                  >
                    {formatBetaLabel(profileStatus, betaProfileStatusLabels)}
                  </span>
                </p>
              </div>
              {!canReport && (
                <p className="mt-3 text-sm leading-6 text-stone-600">
                  Votre profil doit être accepté avant d’accéder aux campagnes et d’envoyer des signalements.
                </p>
              )}
            </div>
            <Link className="mt-5 inline-flex items-center gap-2 rounded-xl border border-brand-100 px-4 py-3 text-sm font-semibold text-brand-700 transition hover:bg-brand-50" to="/beta/profile">
              <Pencil size={16} aria-hidden="true" />
              Modifier mon profil
            </Link>
          </article>
        </section>

        <section className="mb-8">
          <div className="mb-4 flex items-center justify-between">
            <div>
              <h2 className="text-2xl font-bold text-brand-900">Campagnes à tester</h2>
              <p className="mt-1 text-sm text-stone-600">
                Cliquez sur une campagne pour consulter les consignes avant de créer un signalement lié.
              </p>
            </div>
          </div>
          {campaigns.length === 0 ? (
            <div className="rounded-2xl border border-dashed border-stone-300 bg-white p-6 text-sm text-stone-600">
              {canReport ? 'Aucune campagne disponible actuellement.' : 'Les campagnes apparaîtront ici après acceptation de votre profil.'}
            </div>
          ) : (
            <div className="grid gap-4 md:grid-cols-2">
              {campaigns.map((c) => (
                <article key={c.id} className="rounded-2xl border border-stone-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                  <div className="flex items-start justify-between gap-4">
                    <button
                      type="button"
                      onClick={() => openCampaignDetails(c)}
                      className="text-left text-lg font-bold text-brand-900 underline-offset-4 transition hover:text-brand-700 hover:underline focus:outline-none focus:ring-4 focus:ring-brand-100"
                    >
                      {c.name}
                    </button>
                    <span className="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-200">
                      Active
                    </span>
                  </div>
                  <p className="mt-3 text-sm leading-6 text-stone-600">{c.description}</p>
                  <div className="mt-4 grid gap-2 border-t border-stone-100 pt-4 text-xs text-stone-500">
                    <p>Début : {formatDate(c.startsAt)}</p>
                    <p>Fin : {formatDate(c.endsAt)}</p>
                  </div>
                </article>
              ))}
            </div>
          )}
        </section>
        {viewedCampaign && (
          <Dialog open={Boolean(viewedCampaign)} onClose={() => setViewedCampaign(null)} className="relative z-50">
            <DialogBackdrop className="fixed inset-0 bg-brand-900/70" />
            <div className="fixed inset-0 flex items-center justify-center px-4 py-6">
              <DialogPanel className="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-xl border border-brand-100 bg-white p-6 shadow-2xl">
                <header className="space-y-4">
                  <div className="flex flex-wrap items-start justify-between gap-4">
                    <div>
                      <button
                        type="button"
                        className="mb-3 inline-flex items-center justify-center rounded-lg border border-stone-200 px-4 py-3 text-sm font-semibold text-stone-700 transition hover:bg-stone-50 focus:outline-none focus:ring-4 focus:ring-stone-100"
                        onClick={() => setViewedCampaign(null)}
                      >
                        Fermer
                      </button>
                      <DialogTitle className="text-2xl font-bold text-brand-900">
                        {viewedCampaign.name}
                      </DialogTitle>
                    </div>
                    <div className="flex items-start gap-2">
                      <div className="max-w-xs text-right">
                        <p className="mb-2 text-sm leading-5 text-stone-600">
                          {viewedCampaignCanReport
                            ? 'Consultez les consignes avant d’envoyer un signalement lié à cette campagne.'
                            : 'Cette campagne n’est plus ouverte aux signalements.'}
                        </p>
                        <button
                          type="button"
                          onClick={() => openCampaignReport(viewedCampaign)}
                          disabled={!viewedCampaignCanReport}
                          className="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-600 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700 focus:outline-none focus:ring-4 focus:ring-brand-100 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                          <Plus size={16} />
                          Envoyer un signalement
                        </button>
                      </div>
                    </div>
                  </div>
                </header>

                <div className="mt-6 space-y-5">
                  <div className="rounded-2xl bg-brand-50 p-5">
                    <h3 className="font-semibold text-brand-900">Informations de campagne</h3>
                    <p className="mt-2 whitespace-pre-wrap text-sm leading-6 text-stone-700">
                      {viewedCampaign.description}
                    </p>
                  </div>
                  <dl className="grid gap-3 text-sm sm:grid-cols-3">
                    <div className="rounded-xl border border-stone-200 p-3">
                      <dt className="text-xs font-semibold uppercase tracking-[0.12em] text-stone-500">État</dt>
                      <dd className="mt-1 font-semibold text-emerald-700">
                        {formatBetaLabel(viewedCampaign.status, {
                          ...campaignStateLabels,
                          active: viewedCampaignCanReport ? 'Active' : 'Clôturée',
                        })}
                      </dd>
                    </div>
                    <div className="rounded-xl border border-stone-200 p-3">
                      <dt className="text-xs font-semibold uppercase tracking-[0.12em] text-stone-500">Début</dt>
                      <dd className="mt-1 text-stone-800">{formatDate(viewedCampaign.startsAt)}</dd>
                    </div>
                    <div className="rounded-xl border border-stone-200 p-3">
                      <dt className="text-xs font-semibold uppercase tracking-[0.12em] text-stone-500">Fin</dt>
                      <dd className="mt-1 text-stone-800">{formatDate(viewedCampaign.endsAt)}</dd>
                    </div>
                  </dl>
                  <section className="rounded-2xl border border-stone-200 bg-white p-5">
                    <div className="flex flex-wrap items-center justify-between gap-2">
                      <h3 className="font-semibold text-brand-900">Suivis liés à cette campagne</h3>
                      <span className="rounded-full bg-stone-50 px-3 py-1 text-xs font-semibold text-stone-600 ring-1 ring-stone-200">
                        {viewedCampaignReports.length} signalement{viewedCampaignReports.length > 1 ? 's' : ''}
                      </span>
                    </div>
                    {viewedCampaignReports.length === 0 ? (
                      <p className="mt-3 text-sm text-stone-600">
                        Aucun signalement lié à cette campagne pour le moment.
                      </p>
                    ) : (
                      <div className="mt-4 grid gap-3">
                        {viewedCampaignReports.map((report) => (
                          <article key={report.id} className="rounded-xl border border-stone-100 bg-stone-50 p-4">
                            <div className="flex flex-wrap items-start justify-between gap-3">
                              <div>
                                <h4 className="font-semibold text-brand-900">{report.title}</h4>
                                <p className="mt-1 text-xs text-stone-500">
                                  Date du signalement : {formatDate(report.createdAt)}
                                </p>
                              </div>
                              <div className="grid gap-2 text-xs font-semibold">
                                <p className={`rounded-lg px-3 py-2 ring-1 ${badgeClassName(report.severity)}`}>
                                  Priorité : {formatBetaLabel(report.severity, severityLabels)}
                                </p>
                                <p className={`rounded-lg px-3 py-2 ring-1 ${badgeClassName(report.status)}`}>
                                  État : {formatBetaLabel(report.status, bugReportStatusLabels)}
                                </p>
                              </div>
                            </div>
                            <div className="mt-3 flex justify-end">
                              <button
                                type="button"
                                className="inline-flex items-center gap-2 rounded-lg border border-brand-100 bg-white px-4 py-2 text-xs font-semibold text-brand-700 transition hover:bg-brand-50"
                                onClick={() => {
                                  setViewedCampaign(null);
                                  setSelectedReportId(report.id);
                                }}
                              >
                                <MessageSquare size={14} />
                                Ouvrir le suivi
                              </button>
                            </div>
                          </article>
                        ))}
                      </div>
                    )}
                  </section>
                </div>

              </DialogPanel>
            </div>
          </Dialog>
        )}

        {selectedReportId !== null && activeReport && (
          <Dialog open={selectedReportId !== null} onClose={() => setSelectedReportId(null)} className="relative z-50">
            <DialogBackdrop className="fixed inset-0 bg-brand-900/70" />
            <div className="fixed inset-0 flex items-center justify-center px-4 py-6">
              <DialogPanel className="flex max-h-[88vh] w-full max-w-3xl flex-col rounded-xl border border-brand-100 bg-white shadow-2xl">
                <header className="border-b border-stone-200 p-5">
                  <div className="flex items-start justify-between gap-4">
                    <div>
                      <p className="text-xs font-semibold uppercase tracking-[0.2em] text-brand-700">
                        Suivi du signalement
                      </p>
                      <DialogTitle className="mt-1 font-bold text-xl text-brand-900">
                        {activeReport.title}
                      </DialogTitle>
                      <p className="mt-1 text-xs text-stone-500">
                        {activeReport.campaign ? `Campagne : ${activeReport.campaign}` : 'Signalement général'}.
                        {' '}
                        Date du signalement : {formatDate(activeReport.createdAt)}
                      </p>
                    </div>
                    <button
                      type="button"
                      className="rounded-full p-1 text-stone-500 transition hover:bg-stone-100 hover:text-stone-700"
                      onClick={() => setSelectedReportId(null)}
                      aria-label="Fermer le suivi"
                    >
                      <X size={20} />
                    </button>
                  </div>
                  <div className="mt-4 grid gap-2 text-xs font-semibold">
                    <p className={`rounded-lg px-3 py-2 ring-1 ${badgeClassName(activeReport.severity)}`}>
                      Gravité : {formatBetaLabel(activeReport.severity, severityLabels)}
                    </p>
                    <p className={`rounded-lg px-3 py-2 ring-1 ${badgeClassName(activeReport.status)}`}>
                      État : {formatBetaLabel(activeReport.status, bugReportStatusLabels)}
                    </p>
                  </div>
                </header>

                <div className="max-h-56 overflow-y-auto border-b border-stone-200 bg-stone-50 p-5 text-sm text-stone-700">
                  <div className="grid gap-4 md:grid-cols-2">
                    <section className="md:col-span-2">
                      <h3 className="font-semibold text-stone-900">Signalement initial</h3>
                      <p className="mt-1 whitespace-pre-wrap leading-6">{activeReport.description}</p>
                    </section>
                    {activeReport.expectedBehavior && (
                      <section>
                        <h3 className="font-semibold text-stone-900">Résultat attendu</h3>
                        <p className="mt-1 whitespace-pre-wrap leading-6">{activeReport.expectedBehavior}</p>
                      </section>
                    )}
                    {activeReport.actualBehavior && (
                      <section>
                        <h3 className="font-semibold text-stone-900">Résultat constaté</h3>
                        <p className="mt-1 whitespace-pre-wrap leading-6">{activeReport.actualBehavior}</p>
                      </section>
                    )}
                  </div>
                  {(activeReport.attachmentUrls ?? []).length > 0 && (
                    <div className="mt-4">
                      <strong>Captures :</strong>
                      <ul className="mt-1 space-y-1">
                        {(activeReport.attachmentUrls ?? []).map((url, index) => (
                          <li key={url}>
                            <a className="text-brand-700 underline" href={resolveBetaAttachmentUrl(url)} target="_blank" rel="noreferrer">
                              Ouvrir la capture {index + 1}
                            </a>
                          </li>
                        ))}
                      </ul>
                    </div>
                  )}
                </div>

                <div className="flex items-center gap-2 border-b border-stone-200 bg-white px-5 py-3">
                  <MessageSquare size={16} className="text-brand-700" />
                  <h3 className="text-sm font-bold text-brand-900">Conversation</h3>
                </div>
                <div className="flex-1 overflow-y-auto p-4 space-y-4 bg-stone-50/50">
                  {loadingComments ? (
                    <p className="text-center text-stone-500 text-sm">Chargement des messages...</p>
                  ) : comments.length === 0 ? (
                    <p className="text-center text-stone-400 text-sm py-4">Pas encore de message. L'équipe technique vous répondra très bientôt ici !</p>
                  ) : (
                    comments.map((c) => {
                      const isAdminMsg = c.author.role === 'admin';
                      const authorLabel = isAdminMsg ? 'Support Hociatec' : 'Vous';
                      const messageDate = new Date(c.createdAt).toLocaleString();

                      return (
                        <div
                          key={c.id}
                          className={`flex flex-col max-w-[85%] ${
                            !isAdminMsg ? 'ml-auto items-end' : 'mr-auto items-start'
                          }`}
                        >
                          <div
                            className={`p-3 rounded-lg text-sm ${
                              !isAdminMsg
                                ? 'bg-brand-700 text-white rounded-br-none'
                                : 'bg-white border border-stone-200 text-stone-800 rounded-bl-none'
                            }`}
                          >
                            <p className="whitespace-pre-wrap">
                              <span className="font-semibold">{authorLabel}</span>
                              {' '}
                              <span>({messageDate})</span>
                              {' : '}
                              {c.content}
                            </p>
                          </div>
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
        <BetaBugReportDialog
          open={isCreateReportOpen}
          onClose={() => {
            setIsCreateReportOpen(false);
            setSelectedCampaign(null);
          }}
          campaignId={selectedCampaign?.id}
          campaignName={selectedCampaign?.name}
        />
      </PageContainer>
    </SiteLayout>
  );
};
