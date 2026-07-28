import { Link } from 'react-router';
import { ArrowRight, CircleAlert, CircleCheckBig } from 'lucide-react';

import type { AdminDashboardDto } from '@/features/admin/customers/api';
import { formatFrenchDateTime } from '@/shared/lib/formatters';

export const AdminDashboardNotifications = ({ dashboard }: { dashboard: AdminDashboardDto }) => (
  <section className="space-y-4">
    <div className="flex flex-wrap items-end justify-between gap-3">
      <div>
        <h3 className="text-lg font-semibold text-white">Notifications admin</h3>
        <p className="text-sm text-stone-400">Devis acceptés, commandes à régler, emails et paiements récents.</p>
      </div>
      <Link to="/admin/quotes" className="text-sm font-medium text-brand-300 underline">Voir les devis</Link>
    </div>
    <div className="grid gap-3 xl:grid-cols-2">
      {(dashboard.notifications ?? []).length === 0 ? (
        <div className="rounded-2xl border border-brand-700 bg-brand-800/50 p-5 text-sm text-stone-500">Aucune notification récente.</div>
      ) : (dashboard.notifications ?? []).map((item) => {
        const isAction = item.severity === 'action';
        const isDanger = item.severity === 'danger';
        return (
          <article key={item.id} className={`flex gap-3 rounded-2xl border p-4 ${isDanger ? 'border-red-500/40 bg-red-500/10' : isAction ? 'border-amber-400/40 bg-amber-400/10' : 'border-brand-700 bg-brand-800/50'}`}>
            <div className="mt-0.5">{isDanger || isAction ? <CircleAlert className={`h-5 w-5 ${isDanger ? 'text-red-300' : 'text-amber-300'}`} /> : <CircleCheckBig className="h-5 w-5 text-emerald-300" />}</div>
            <div className="min-w-0 flex-1">
              <div className="font-semibold text-white">{item.title}</div>
              <div className="mt-1 truncate text-sm text-stone-500">{item.message || item.type}</div>
              <div className="mt-2 text-xs text-stone-400">{formatFrenchDateTime(item.createdAt)}</div>
            </div>
            <Link to={item.to} className="mt-0.5 inline-flex h-8 flex-none items-center gap-1 rounded-lg border border-brand-200 px-3 text-xs font-semibold text-brand-300 transition hover:border-brand-500 hover:bg-brand-50">Ouvrir <ArrowRight className="h-3.5 w-3.5" /></Link>
          </article>
        );
      })}
    </div>
  </section>
);
