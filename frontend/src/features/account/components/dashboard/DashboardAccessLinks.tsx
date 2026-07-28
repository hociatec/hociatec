import {
  BadgePercent,
  CalendarDays,
  FileText,
  FlaskConical,
  GraduationCap,
  Heart,
  MessageSquareText,
  MapPin,
  Package,
  UserRound,
} from 'lucide-react';
import { Link } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { fetchMyBetaProfile } from '@/features/betaTest/api/betaApi';
import { useAuth } from '@/features/auth/hooks/useAuth';
import { DashboardAuditCard } from './DashboardAuditCard';

export const DashboardAccessLinks = () => {
  const { status, user } = useAuth();
  const isAuthenticated = status === 'authenticated' && Boolean(user);

  const { data: betaProfile } = useQuery({
    queryKey: ['betaProfile'],
    queryFn: fetchMyBetaProfile,
    enabled: isAuthenticated,
    retry: false,
  });

  const isBetaTester = Boolean(betaProfile);

  const destinations = [
    { icon: <Package />, title: 'Commandes', to: '/orders/me' },
    { icon: <FileText />, title: 'Devis', to: '/quotes/me' },
    { icon: <CalendarDays />, title: 'Rendez-vous', to: '/appointments/me' },
    { icon: <GraduationCap />, title: 'Formations', to: '/trainings/me' },
    { icon: <BadgePercent />, title: 'Bons', to: '/vouchers/me' },
    { icon: <Package />, title: 'Reprises', to: '/reprises' },
    isBetaTester
      ? { icon: <FlaskConical />, title: 'Espace Bêta', to: '/beta' }
      : { icon: <FlaskConical />, title: 'Devenir Bêta-testeur', to: '/beta-test' },
  ];

  return (
    <>
      <section className="client-dashboard__panel" aria-labelledby="dashboard-destinations-title">
        <div className="client-dashboard__panel-heading">
          <h2 id="dashboard-destinations-title">Aller à</h2>
        </div>
        <div className="client-dashboard__destination-list">
          {destinations.map((destination) => (
            <Link
              key={destination.to}
              to={destination.to}
              className="client-dashboard__destination-card"
            >
              <span aria-hidden="true">{destination.icon}</span>
              <strong>{destination.title}</strong>
            </Link>
          ))}
          <DashboardAuditCard />
        </div>
      </section>
      <section className="client-dashboard__panel" aria-labelledby="dashboard-settings-title">
        <div className="client-dashboard__panel-heading">
          <h2 id="dashboard-settings-title">Paramètres</h2>
        </div>
        <div className="client-dashboard__settings-list">
          <Link to="/profile">
            <UserRound aria-hidden="true" />
            <span>Profil</span>
          </Link>
          <Link to="/profile/addresses">
            <MapPin aria-hidden="true" />
            <span>Adresses</span>
          </Link>
          <Link to="/profile/communication-preferences">
            <MessageSquareText aria-hidden="true" />
            <span>Préférences de communication</span>
          </Link>
          <Link to="/favorites">
            <Heart aria-hidden="true" />
            <span>Favoris</span>
          </Link>
        </div>
      </section>
    </>
  );
};
