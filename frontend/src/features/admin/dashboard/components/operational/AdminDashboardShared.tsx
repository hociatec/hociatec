import { Link } from 'react-router';
import { ArrowRight } from 'lucide-react';

export const PanelTitle = ({
  helper,
  linkLabel,
  title,
  to,
}: {
  helper: string;
  linkLabel: string;
  title: string;
  to: string;
}) => (
  <div className="mb-4 flex items-center justify-between gap-3">
    <div>
      <h3 className="text-lg font-semibold text-white">{title}</h3>
      <p className="text-sm text-stone-400">{helper}</p>
    </div>
    <Link to={to} className="text-sm font-medium text-brand-300 underline">
      {linkLabel}
    </Link>
  </div>
);

export const DashboardCardAction = ({
  className = '',
  label,
  to,
}: {
  className?: string;
  label: string;
  to: string;
}) => (
  <Link
    to={to}
    className={`inline-flex items-center gap-1 rounded-lg border border-brand-200 px-3 py-2 text-xs font-semibold text-brand-300 transition hover:border-brand-500 hover:bg-brand-50 ${className}`}
  >
    {label}
    <ArrowRight className="h-3.5 w-3.5" />
  </Link>
);
