import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import {
  BadgePercent,
  CalendarDays,
  FileText,
  Heart,
  MapPin,
  Package,
  ShieldCheck,
  UserRound,
} from 'lucide-react';

import { fetchMyAppointments } from '@/features/appointments/api';
import type { AppointmentItem } from '@/features/appointments/types';
import { fetchMyAudits, type AuditListItemDto } from '@/features/audits/api';
import { useAuth } from '@/features/auth/hooks/useAuth';
import { fetchMyOrders, fetchPendingReviews, formatOrderStatusFr, type OrderDto, type PendingReviewDto } from '@/features/orders/api';
import { fetchMyQuotes, formatQuoteStatus } from '@/features/quotes/api';
import { fetchMyVouchers, type MyVoucherDto } from '@/features/vouchers/api';
import { SiteLayout } from '@/shared/components/SiteLayout';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';

import './ClientDashboardPage.css';

type LoadState = 'loading' | 'success' | 'error';

interface QuoteSummary {
  id: number;
  number?: string | null;
  status?: string | null;
  totals?: {
    ttc?: number | null;
  } | null;
  createdAt?: string | null;
  validUntil?: string | null;
}

const formatPrice = (valueInCents?: number | null) =>
  typeof valueInCents === 'number'
    ? new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(valueInCents / 100)
    : '-';

const formatDate = (value?: string | null) => {
  if (!value) return '-';
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return '-';
  return date.toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
  });
};

const formatDateTime = (value?: string | null) => {
  if (!value) return '-';
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return '-';
  return date.toLocaleString('fr-FR', {
    day: '2-digit',
    month: 'short',
    hour: '2-digit',
    minute: '2-digit',
  });
};

const isVoucherUsable = (voucher: MyVoucherDto) => {
  if (!voucher.isActive) return false;
  const now = Date.now();
  if (voucher.startsAt && new Date(voucher.startsAt).getTime() > now) return false;
  if (voucher.endsAt && new Date(voucher.endsAt).getTime() < now) return false;
  return true;
};

export const ClientDashboardPage = () => {
  useDocumentTitle('Mon espace');
  const { user } = useAuth();

  const [orders, setOrders] = useState<OrderDto[]>([]);
  const [quotes, setQuotes] = useState<QuoteSummary[]>([]);
  const [appointments, setAppointments] = useState<AppointmentItem[]>([]);
  const [audits, setAudits] = useState<AuditListItemDto[]>([]);
  const [vouchers, setVouchers] = useState<MyVoucherDto[]>([]);
  const [pendingReviews, setPendingReviews] = useState<PendingReviewDto[]>([]);
  const [state, setState] = useState<LoadState>('loading');

  useEffect(() => {
    let cancelled = false;
    setState('loading');

    void Promise.allSettled([
      fetchMyOrders(),
      fetchMyQuotes(),
      fetchMyAppointments(),
      fetchMyAudits(),
      fetchMyVouchers(),
      fetchPendingReviews(),
    ]).then((results) => {
      if (cancelled) return;

      const [ordersResult, quotesResult, appointmentsResult, auditsResult, vouchersResult, reviewsResult] = results;

      if (ordersResult.status === 'fulfilled') setOrders(ordersResult.value);
      if (quotesResult.status === 'fulfilled') setQuotes(quotesResult.value);
      if (appointmentsResult.status === 'fulfilled') setAppointments(appointmentsResult.value.upcoming ?? []);
      if (auditsResult.status === 'fulfilled') setAudits(auditsResult.value);
      if (vouchersResult.status === 'fulfilled') setVouchers(vouchersResult.value);
      if (reviewsResult.status === 'fulfilled') setPendingReviews(reviewsResult.value);

      setState(results.some((result) => result.status === 'rejected') ? 'error' : 'success');
    });

    return () => {
      cancelled = true;
    };
  }, []);

  const firstName = user?.firstName?.trim() || 'Bonjour';
  const latestOrder = orders[0] ?? null;
  const latestQuote = quotes[0] ?? null;
  const nextAppointment = appointments[0] ?? null;
  const activeAudit = audits.find((audit) => audit.status !== 'done') ?? audits[0] ?? null;
  const usableVouchers = vouchers.filter(isVoucherUsable);

  return (
    <SiteLayout headerVariant="light">
      <main className="client-dashboard">
        <header className="client-dashboard__hero">
          <div>
            <p className="client-dashboard__eyebrow">Mon espace</p>
            <h1>{firstName}, voici le suivi de votre activité</h1>
            <p>
              Retrouvez vos commandes, devis, rendez-vous, audits et avantages depuis une seule vue.
            </p>
          </div>
        </header>

        {state === 'loading' && <div className="client-dashboard__notice">Chargement de votre espace...</div>}
        {state === 'error' && (
          <div className="client-dashboard__notice client-dashboard__notice--warning">
            Certaines informations n’ont pas pu être chargées. Les accès rapides restent disponibles.
          </div>
        )}

        <section className="client-dashboard__grid" aria-label="Suivis principaux">
          <article className="client-dashboard__panel client-dashboard__panel--wide">
            <div className="client-dashboard__panel-header">
              <div>
                <h2>Mes accès</h2>
                <p>Vos informations, vos suivis et tout votre historique.</p>
              </div>
            </div>
            <div className="client-dashboard__account-links">
              <Link to="/orders/me">
                <Package aria-hidden="true" />
                <span>Commandes</span>
              </Link>
              <Link to="/appointments/me">
                <CalendarDays aria-hidden="true" />
                <span>Rendez-vous</span>
              </Link>
              <Link to="/quotes/me">
                <FileText aria-hidden="true" />
                <span>Devis</span>
              </Link>
              <Link to="/audits/me">
                <ShieldCheck aria-hidden="true" />
                <span>Audits</span>
              </Link>
              <Link to="/vouchers/me">
                <BadgePercent aria-hidden="true" />
                <span>Bons</span>
              </Link>
              <Link to="/profile">
                <UserRound aria-hidden="true" />
                <span>Profil</span>
              </Link>
              <Link to="/profile/addresses">
                <MapPin aria-hidden="true" />
                <span>Adresses</span>
              </Link>
              <Link to="/favorites">
                <Heart aria-hidden="true" />
                <span>Favoris</span>
              </Link>
            </div>
          </article>

          <article className="client-dashboard__panel client-dashboard__panel--wide">
            <div className="client-dashboard__panel-header">
              <div>
                <h2>Dernière commande</h2>
                <p>Suivi, facture et avis produits.</p>
              </div>
            </div>
            {latestOrder ? (
              <Link to={`/orders/${latestOrder.id}`} className="client-dashboard__record">
                <div>
                  <strong>{latestOrder.number}</strong>
                  <span>{formatDate(latestOrder.createdAt)}</span>
                </div>
                <div>
                  <strong>{formatPrice(latestOrder.totalPriceCents)}</strong>
                  <span>{latestOrder.statusLabel ?? formatOrderStatusFr(latestOrder.status)}</span>
                </div>
                {pendingReviews.length > 0 ? (
                  <div>
                    <strong>{pendingReviews.length} avis</strong>
                    <span>à laisser</span>
                  </div>
                ) : null}
              </Link>
            ) : (
              <EmptyState text="Aucune commande pour le moment." actionLabel="Voir le catalogue" to="/catalogue/vente" />
            )}
          </article>

          <article className="client-dashboard__panel">
            <div className="client-dashboard__panel-header">
              <div>
                <h2>Prochain rendez-vous</h2>
                <p>Votre intervention à venir.</p>
              </div>
            </div>
            {nextAppointment ? (
              <Link to="/appointments/me" className="client-dashboard__compact-record" aria-label="Ouvrir mes rendez-vous">
                <CalendarDays aria-hidden="true" />
                <strong>{nextAppointment.prestation.name}</strong>
                <span>{formatDateTime(nextAppointment.startAt)}</span>
              </Link>
            ) : (
              <EmptyState text="Aucun rendez-vous planifié." actionLabel="Réserver" to="/appointments/book" />
            )}
          </article>

          <article className="client-dashboard__panel">
            <div className="client-dashboard__panel-header">
              <div>
                <h2>Dernier devis</h2>
                <p>Proposition et PDF.</p>
              </div>
            </div>
            {latestQuote ? (
              <Link to={`/quotes/me/${latestQuote.id}`} className="client-dashboard__compact-record">
                <FileText aria-hidden="true" />
                <strong>{latestQuote.number ?? `Devis #${latestQuote.id}`}</strong>
                <span>{formatQuoteStatus(latestQuote.status)} · {formatPrice(latestQuote.totals?.ttc)}</span>
              </Link>
            ) : (
              <EmptyState text="Aucun devis enregistré." actionLabel="Créer" to="/devis/nouveau" />
            )}
          </article>

          <article className="client-dashboard__panel">
            <div className="client-dashboard__panel-header">
              <div>
                <h2>Audit</h2>
                <p>Demandes et rapports.</p>
              </div>
            </div>
            {activeAudit ? (
              <Link to={`/audits/me/${activeAudit.id}`} className="client-dashboard__compact-record">
                <ShieldCheck aria-hidden="true" />
                <strong>{activeAudit.number}</strong>
                <span>{activeAudit.url}</span>
              </Link>
            ) : (
              <EmptyState text="Aucun audit demandé." actionLabel="Demander" to="/audits/request" />
            )}
          </article>

          <article className="client-dashboard__panel">
            <div className="client-dashboard__panel-header">
              <div>
                <h2>Avantages</h2>
                <p>Bons de réduction actifs.</p>
              </div>
            </div>
            {usableVouchers.length > 0 ? (
              <Link to="/vouchers/me" className="client-dashboard__compact-record">
                <BadgePercent aria-hidden="true" />
                <strong>{usableVouchers.length} bon{usableVouchers.length > 1 ? 's' : ''} utilisable{usableVouchers.length > 1 ? 's' : ''}</strong>
                <span>{usableVouchers[0]?.code}</span>
              </Link>
            ) : (
              <div className="client-dashboard__compact-record client-dashboard__compact-record--static">
                <BadgePercent aria-hidden="true" />
                <strong>Aucun bon actif</strong>
                <span>Vos bons de réduction apparaîtront ici.</span>
              </div>
            )}
          </article>
        </section>
      </main>
    </SiteLayout>
  );
};

const EmptyState = ({ text, actionLabel, to }: { text: string; actionLabel: string; to: string }) => (
  <div className="client-dashboard__empty">
    <span>{text}</span>
    <Link to={to}>{actionLabel}</Link>
  </div>
);
