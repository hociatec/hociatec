import { ShieldCheck } from 'lucide-react';
import { Link } from 'react-router-dom';

export const DashboardAuditCard = () => (
  <Link to="/audits/me" className="client-dashboard__destination-card">
    <span aria-hidden="true"><ShieldCheck /></span><strong>Audits</strong>
  </Link>
);
