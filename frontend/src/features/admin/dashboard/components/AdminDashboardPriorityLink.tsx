import { Link } from 'react-router-dom';

export const AdminDashboardPriorityLink = ({ to, label, value, helper }: { to: string; label: string; value: number; helper: string }) => <div className="rounded-2xl border border-brand-700 bg-brand-800/50 p-5"><div className="text-sm text-stone-400">{label}</div><div className="mt-2 text-2xl font-semibold text-white">{value}</div><Link to={to} className="mt-3 inline-flex items-center rounded-lg border border-brand-200 px-3 py-2 text-xs font-semibold text-brand-300 transition hover:border-brand-500 hover:bg-brand-50">{helper}</Link></div>;
