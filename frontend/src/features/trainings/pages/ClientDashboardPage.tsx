import { useEffect, useState, type ReactNode } from 'react';
import { Link } from 'react-router-dom';
import {
  BadgePercent,
  CalendarDays,
  FileText,
  GraduationCap,
  Heart,
  MapPin,
  Package,
  ShieldCheck,
  UserRound,
} from 'lucide-react';

import { fetchMyAppointments } from '@/features/appointments/api';
import type { AppointmentItem } from '@/features/appointments/types';
import { useAuth } from '@/features/auth/hooks/useAuth';
import { fetchPendingReviews, type PendingReviewDto } from '@/features/orders/api';
import { convertMyLoyalty, fetchMyLoyalty, type LoyaltyBalanceDto } from '@/features/loyalty/api';
import { fetchMyQuotes, type QuoteDto } from '@/features/quotes/api';
import {
  fetchMyTrainingEnrollments,
  type TrainingEnrollmentDto,
} from '@/features/trainings/api';
import { SiteLayout } from '@/shared/components/SiteLayout';
import { useToast } from '@/shared/components/ui/toast';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { formatFrenchNumber, formatOptionalEuroCents, formatOptionalFrenchDateTime } from '@/shared/lib/formatters';

import '@/features/account/pages/ClientDashboardPage.css';
import '@/features/loyalty/client-dashboard-refresh.css';

type LoadState = 'loading' | 'success' | 'error';

const emptyLoyalty: LoyaltyBalanceDto = {
  points: 0,
  euroCents: 0,
  pointsPerEuroEarned: 10,
  pointsPerEuroConverted: 100,
};

interface DashboardAction {
  icon: ReactNode;
  title: string;
  detail: string;
  to: string;
}

export const ClientDashboardPage = () => {
  useDocumentTitle('Mon espace');
  const { user } = useAuth();
  const toast = useToast();

  const [quotes, setQuotes] = useState<QuoteDto[]>([]);
  const [appointments, setAppointments] = useState<AppointmentItem[]>([]);
  const [trainings, setTrainings] = useState<TrainingEnrollmentDto[]>([]);
  const [pendingReviews, setPendingReviews] = useState<PendingReviewDto[]>([]);
  const [loyalty, setLoyalty] = useState<LoyaltyBalanceDto>(emptyLoyalty);
  const [convertPoints, setConvertPoints] = useState('100');
  const [state, setState] = useState<LoadState>('loading');
  const [conversionState, setConversionState] = useState<'idle' | 'saving'>('idle');
  const [loadedAtMs, setLoadedAtMs] = useState(0);

  useEffect(() => {
    let cancelled = false;
    setState('loading');

    void Promise.allSettled([
      fetchMyQuotes(),
      fetchMyAppointments(),
      fetchMyTrainingEnrollments(),
      fetchPendingReviews(),
      fetchMyLoyalty(),
    ]).then((results) => {
      if (cancelled) return;

      const [quotesResult, appointmentsResult, trainingsResult, reviewsResult, loyaltyResult] = results;
      if (quotesResult.status === 'fulfilled') setQuotes(quotesResult.value);
      if (appointmentsResult.status === 'fulfilled') setAppointments(appointmentsResult.value.upcoming ?? []);
      if (trainingsResult.status === 'fulfilled') setTrainings(trainingsResult.value);
      if (reviewsResult.status === 'fulfilled') setPendingReviews(reviewsResult.value);
      if (loyaltyResult.status === 'fulfilled') setLoyalty(loyaltyResult.value);

      const criticalResults = [quotesResult, appointmentsResult, reviewsResult];
      setLoadedAtMs(Date.now());
      setState(criticalResults.some((result) => result.status === 'rejected') ? 'error' : 'success');
    });

    return () => {
      cancelled = true;
    };
  }, []);

  const firstName = user?.firstName?.trim() || 'Bonjour';
  const quoteToAnswer = quotes.find((quote) => (quote.statusCode ?? quote.status) === 'sent' && !quote.convertedOrder) ?? null;
  const nextAppointment = appointments[0] ?? null;
  const nextTraining = trainings
    .filter((training) => training.status !== 'cancelled' && new Date(training.scheduledStartsAt).getTime() >= loadedAtMs)
    .sort((a, b) => new Date(a.scheduledStartsAt).getTime() - new Date(b.scheduledStartsAt).getTime())[0] ?? null;
  const firstPendingReview = pendingReviews[0] ?? null;
  const dashboardActions: DashboardAction[] = [
    pendingReviews.length > 0 ? {
      icon: <Package />,
      title: `Laisser ${pendingReviews.length} avis produit${pendingReviews.length > 1 ? 's' : ''}`,
      detail: firstPendingReview ? `Commande ${firstPendingReview.orderNumber}` : 'Commande à compléter',
      to: firstPendingReview ? `/orders/${firstPendingReview.orderId}` : '/orders/me',
    } : null,
    nextAppointment ? {
      icon: <CalendarDays />,
      title: 'Préparer votre rendez-vous',
      detail: formatOptionalFrenchDateTime(nextAppointment.startAt),
      to: '/appointments/me',
    } : null,
    quoteToAnswer ? {
      icon: <FileText />,
      title: `Répondre au devis ${quoteToAnswer.number ?? `#${quoteToAnswer.id}`}`,
      detail: 'Accepter ou refuser la proposition',
      to: `/quotes/me/${quoteToAnswer.id}`,
    } : null,
    nextTraining ? {
      icon: <GraduationCap />,
      title: 'Voir votre formation à venir',
      detail: `${nextTraining.session.training.title} · ${formatOptionalFrenchDateTime(nextTraining.scheduledStartsAt)}`,
      to: `/trainings/me/${nextTraining.id}`,
    } : null,
  ].filter(Boolean) as DashboardAction[];

  const conversionPoints = Math.floor((Number.parseInt(convertPoints, 10) || 0) / 100) * 100;
  const conversionEuroCents = Math.floor(conversionPoints / loyalty.pointsPerEuroConverted) * 100;
  const hasConvertiblePoints = loyalty.points > 0;

  useEffect(() => {
    if (loyalty.points <= 0) {
      setConvertPoints('0');
      return;
    }

    const currentPoints = Number.parseInt(convertPoints, 10) || 0;
    if (currentPoints <= 0 || currentPoints > loyalty.points) {
      setConvertPoints(String(Math.floor(loyalty.points / 100) * 100 || loyalty.points));
    }
  }, [convertPoints, loyalty.points]);

  const handleConvert = () => {
    setConversionState('saving');
    void convertMyLoyalty(conversionPoints)
      .then((result) => {
        setLoyalty(result.loyalty);
        setConvertPoints('100');
        toast.show(`Bon ${result.voucher.code} créé pour ${formatOptionalEuroCents(result.voucher.discountValue)}.`, { variant: 'success' });
      })
      .catch((error: unknown) => {
        toast.show(error instanceof Error ? error.message : 'Impossible de convertir vos points.', { variant: 'error' });
      })
      .finally(() => setConversionState('idle'));
  };

  return (
    <SiteLayout headerVariant="light">
      <main className="client-dashboard client-dashboard--refresh" aria-labelledby="client-dashboard-title">
        <header className="client-dashboard__hero client-dashboard__hero--compact">
          <div>
            <h1 id="client-dashboard-title">{firstName}, votre espace en un coup d'oeil</h1>
            <p>Suivez vos dossiers actifs, vos avantages et vos prochaines actions depuis une seule page.</p>
          </div>
        </header>

        {state === 'loading' && (
          <div className="client-dashboard__notice" role="status" aria-live="polite">
            Chargement de votre espace...
          </div>
        )}
        {state === 'error' && (
          <div className="client-dashboard__notice client-dashboard__notice--warning">
            Certaines informations n’ont pas pu être chargées. Les accès rapides restent disponibles.
          </div>
        )}

        <section className="client-dashboard__workspace" aria-label="Tableau de bord">
          <div className="client-dashboard__main-column">
            <section className="client-dashboard__panel client-dashboard__panel--focus" aria-labelledby="dashboard-actions-title">
              <div className="client-dashboard__panel-heading">
                <h2 id="dashboard-actions-title">À faire maintenant</h2>
              </div>
              {dashboardActions.length > 0 ? (
                <div className="client-dashboard__action-list">
                  {dashboardActions.map((action) => <ActionItem key={`${action.to}-${action.title}`} {...action} />)}
                </div>
              ) : (
                <div className="client-dashboard__calm-state">
                  <strong>Rien d'urgent à traiter.</strong>
                  <p>Vos prochaines commandes, rendez-vous, devis ou formations apparaîtront ici dès qu'une action sera utile.</p>
                </div>
              )}
            </section>

            <section id="loyalty" className="client-dashboard__panel client-dashboard__loyalty" aria-labelledby="loyalty-title">
              <div className="client-dashboard__panel-heading">
                <h2 id="loyalty-title">Fidélité</h2>
              </div>
              <div className="client-dashboard__charts">
                <LoyaltyChart label="Points disponibles" value={loyalty.points} max={Math.max(1000, loyalty.points)} display={`${formatFrenchNumber(loyalty.points)} pts`} />
                <LoyaltyChart label="Valeur convertible" value={loyalty.euroCents / 100} max={Math.max(50, loyalty.euroCents / 100)} display={formatOptionalEuroCents(loyalty.euroCents)} />
              </div>
              <div className="client-dashboard__conversion">
                <label>
                  <span>Points à convertir</span>
                  <input
                    type="number"
                    min={hasConvertiblePoints ? 100 : 0}
                    step={100}
                    value={convertPoints}
                    onChange={(event) => setConvertPoints(event.target.value)}
                    readOnly={!hasConvertiblePoints}
                  />
                </label>
                <div>
                  <strong>{formatOptionalEuroCents(conversionEuroCents)}</strong>
                  <span>en bon de réduction</span>
                </div>
                <button type="button" onClick={handleConvert} disabled={conversionState === 'saving' || conversionPoints <= 0 || conversionPoints > loyalty.points}>
                  {conversionState === 'saving' ? 'Conversion...' : 'Convertir'}
                </button>
              </div>
            </section>
          </div>

          <aside className="client-dashboard__side-column" aria-labelledby="dashboard-destinations-title">
            <section className="client-dashboard__panel">
              <div className="client-dashboard__panel-heading">
                <h2 id="dashboard-destinations-title">Aller à</h2>
              </div>
              <div className="client-dashboard__destination-list">
                <DestinationCard icon={<Package />} title="Commandes" to="/orders/me" />
                <DestinationCard icon={<FileText />} title="Devis" to="/quotes/me" />
                <DestinationCard icon={<CalendarDays />} title="Rendez-vous" to="/appointments/me" />
                <DestinationCard icon={<GraduationCap />} title="Formations" to="/trainings/me" />
                <DestinationCard icon={<BadgePercent />} title="Bons" to="/vouchers/me" />
                <DestinationCard icon={<ShieldCheck />} title="Audits" to="/audits/me" />
              </div>
            </section>

            <section className="client-dashboard__panel">
              <div className="client-dashboard__panel-heading">
                <h2>Paramètres</h2>
              </div>
              <div className="client-dashboard__settings-list">
                <Link to="/profile"><UserRound aria-hidden="true" /><span>Profil</span></Link>
                <Link to="/profile/addresses"><MapPin aria-hidden="true" /><span>Adresses</span></Link>
                <Link to="/favorites"><Heart aria-hidden="true" /><span>Favoris</span></Link>
              </div>
            </section>
          </aside>
        </section>
      </main>
    </SiteLayout>
  );
};

const LoyaltyChart = ({ label, value, max, display }: { label: string; value: number; max: number; display: string }) => (
  <div className="client-dashboard__chart" role="img" aria-label={`${label}: ${display}`}>
    <div className="client-dashboard__chart-label">
      <span>{label}</span>
      <strong>{display}</strong>
    </div>
    <progress className="client-dashboard__chart-progress" value={Math.max(0, value)} max={Math.max(1, max)} />
  </div>
);

const ActionItem = ({ icon, title, detail, to }: DashboardAction) => (
  <div className="client-dashboard__action-item">
    <div className="client-dashboard__item-icon" aria-hidden="true">{icon}</div>
    <div>
      <Link to={to} className="client-dashboard__action-title">{title}</Link>
      <p>{detail}</p>
    </div>
  </div>
);

const DestinationCard = ({ icon, title, to }: { icon: ReactNode; title: string; to: string }) => (
  <Link to={to} className="client-dashboard__destination-card">
    <span aria-hidden="true">{icon}</span>
    <strong>{title}</strong>
  </Link>
);
