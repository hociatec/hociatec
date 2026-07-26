import { BadgePercent, CalendarDays, FileText, GraduationCap, Heart, MapPin, Package, UserRound } from 'lucide-react';
import { Link } from 'react-router-dom';

import { DashboardAuditCard } from './DashboardAuditCard';

const destinations = [
  { icon: <Package />, title: 'Commandes', to: '/orders/me' },
  { icon: <FileText />, title: 'Devis', to: '/quotes/me' },
  { icon: <CalendarDays />, title: 'Rendez-vous', to: '/appointments/me' },
  { icon: <GraduationCap />, title: 'Formations', to: '/trainings/me' },
  { icon: <BadgePercent />, title: 'Bons', to: '/vouchers/me' },
];

export const DashboardAccessLinks = () => (
  <>
    <section className="client-dashboard__panel" aria-labelledby="dashboard-destinations-title">
      <div className="client-dashboard__panel-heading"><h2 id="dashboard-destinations-title">Aller à</h2></div>
      <div className="client-dashboard__destination-list">
        {destinations.map((destination) => <Link key={destination.to} to={destination.to} className="client-dashboard__destination-card"><span aria-hidden="true">{destination.icon}</span><strong>{destination.title}</strong></Link>)}
        <DashboardAuditCard />
      </div>
    </section>
    <section className="client-dashboard__panel" aria-labelledby="dashboard-settings-title">
      <div className="client-dashboard__panel-heading"><h2 id="dashboard-settings-title">Paramètres</h2></div>
      <div className="client-dashboard__settings-list">
        <Link to="/profile"><UserRound aria-hidden="true" /><span>Profil</span></Link>
        <Link to="/profile/addresses"><MapPin aria-hidden="true" /><span>Adresses</span></Link>
        <Link to="/favorites"><Heart aria-hidden="true" /><span>Favoris</span></Link>
      </div>
    </section>
  </>
);
