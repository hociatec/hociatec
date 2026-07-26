import { Package } from 'lucide-react';
import { Link } from 'react-router-dom';

import type { DashboardAction } from '@/features/account/types/dashboard';

export const DashboardOrderCard = ({ action }: { action: DashboardAction }) => (
  <DashboardActionCard action={action} icon={<Package />} />
);

const DashboardActionCard = ({
  action,
  icon,
}: {
  action: DashboardAction;
  icon: React.ReactNode;
}) => (
  <div className="client-dashboard__action-item">
    <div className="client-dashboard__item-icon" aria-hidden="true">
      {icon}
    </div>
    <div>
      <Link to={action.to} className="client-dashboard__action-title">
        {action.title}
      </Link>
      <p>{action.detail}</p>
    </div>
  </div>
);

export { DashboardActionCard };
