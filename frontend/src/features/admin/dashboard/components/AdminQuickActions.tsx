import { CalendarDays, FileText, Mail, ShoppingCart, type LucideIcon } from 'lucide-react';
import { Link } from 'react-router';

const actions: Array<{ to: string; label: string; icon: LucideIcon }> = [
  { to: '/admin/orders', label: 'Traiter les commandes', icon: ShoppingCart },
  { to: '/admin/quotes/new', label: 'Créer un devis', icon: FileText },
  {
    to: '/admin/appointments/schedule',
    label: 'Planning RDV',
    icon: CalendarDays,
  },
  { to: '/admin/marketing', label: 'Envoyer une campagne', icon: Mail },
];

export const AdminQuickActions = () => (
  <section className="admin-dashboard__quick-actions" aria-label="Actions rapides">
    {actions.map(({ to, label, icon: Icon }) => (
      <article key={to}>
        <Icon aria-hidden="true" />
        <span>{label}</span>
        <Link to={to}>Ouvrir</Link>
      </article>
    ))}
  </section>
);
