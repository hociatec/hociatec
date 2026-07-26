import { type ReactNode } from 'react';

import { type OperationsOverviewDto } from '@/features/admin/operations/api';
import { LoadingState } from '@/shared/components/ui/page-state';

const cardClass = 'rounded-2xl border border-brand-100 bg-white p-5 shadow-sm';
const secondaryActionClass = 'rounded-xl border border-brand-100 bg-white px-4 py-2 text-sm font-semibold text-stone-700 transition hover:border-brand-300 hover:text-brand-700';

export const operationsUi = {
  cardClass,
  inputClass: 'w-full rounded-xl border border-brand-100 px-3 py-2 text-sm text-brand-900 outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-100',
  primaryActionClass: 'rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-700 disabled:cursor-not-allowed disabled:opacity-50',
  secondaryActionClass,
};

export const OperationsHeader = ({
  message,
  onRefresh,
  status,
}: {
  message: string | null;
  onRefresh: () => void;
  status: 'loading' | 'error' | 'success';
}) => (
  <div className="mb-6 rounded-xl border border-brand-100 bg-white p-5 shadow-sm">
    <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
      <div className="max-w-3xl">
        <p className="text-sm font-semibold uppercase tracking-wide text-brand-700">Tableau de bord opérationnel</p>
        <h1 className="mt-2 text-2xl font-semibold text-brand-900">Ce qui demande une action aujourd’hui</h1>
        <p className="mt-2 text-sm leading-6 text-stone-600">
          Cette page regroupe les tâches de suivi : SAV, remboursements manuels, corrections de stock,
          exports CSV, emails transactionnels et actions groupées sur les commandes.
        </p>
      </div>
      <button className={secondaryActionClass} type="button" onClick={onRefresh}>
        Actualiser
      </button>
    </div>

    {message && (
      <div className="mt-4 rounded-2xl border border-brand-200 bg-brand-50 px-4 py-3 text-sm text-brand-900">
        {message}
      </div>
    )}
    {status === 'loading' && <LoadingState>Chargement des données...</LoadingState>}
    {status === 'error' && <p className="mt-4 text-sm text-red-600">Certaines données n’ont pas pu être chargées.</p>}
  </div>
);

export const OperationsPriorities = ({
  failedEmails,
  hasPriorities,
  overview,
}: {
  failedEmails: number;
  hasPriorities: boolean;
  overview: OperationsOverviewDto;
}) => (
  <section className="mb-8">
    <div className="mb-3 flex items-center justify-between">
      <h2 className="text-lg font-semibold text-brand-900">Priorités</h2>
      <span className={`rounded-full px-3 py-1 text-xs font-semibold ${hasPriorities ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800'}`}>
        {hasPriorities ? 'À vérifier' : 'Aucune urgence'}
      </span>
    </div>
    <div className="grid gap-4 md:grid-cols-4">
      <StatCard label="SAV ouverts" value={overview.support.openCount} helper="Demandes client à traiter ou relancer." tone={overview.support.openCount > 0 ? 'warning' : 'neutral'} />
      <StatCard label="Remboursements" value={overview.refunds.pendingCount} helper="Suivis en attente de décision." tone={overview.refunds.pendingCount > 0 ? 'warning' : 'neutral'} />
      <StatCard
        label="Produits en stock faible"
        value={overview.stock.lowStockCount}
        helper={`Produits publiés avec ${overview.stock.lowStockThreshold ?? 3} unités ou moins.`}
        tone={overview.stock.lowStockCount > 0 ? 'warning' : 'neutral'}
      />
      <StatCard label="Emails échoués" value={failedEmails} helper="Emails transactionnels non envoyés." tone={failedEmails > 0 ? 'danger' : 'neutral'} />
    </div>
    <div className="mt-4 rounded-2xl border border-brand-100 bg-white p-5 shadow-sm">
      <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h3 className="font-semibold text-brand-900">Détail des stocks faibles</h3>
          <p className="mt-1 text-sm text-stone-500">
            Le compteur correspond au nombre de produits publiés sous le seuil, pas au nombre d’unités restantes.
          </p>
        </div>
        <a className={secondaryActionClass + ' text-center'} href="/admin/catalog/products?stock=low">
          Voir tous les stocks faibles
        </a>
      </div>

      {(overview.stock.lowStockItems ?? []).length === 0 ? (
        <p className="mt-4 rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
          Aucun produit publié n’est actuellement sous le seuil.
        </p>
      ) : (
        <div className="mt-4 grid gap-3 md:grid-cols-2">
          {(overview.stock.lowStockItems ?? []).map((product) => (
            <a key={product.id} className="rounded-2xl border border-brand-100 bg-brand-50 p-4 transition hover:border-brand-300 hover:bg-brand-50" href={`/admin/catalog/products/${product.id}/edit`}>
              <div className="flex items-start justify-between gap-3">
                <div>
                  <div className="font-semibold text-brand-900">{product.name}</div>
                  <div className="mt-1 text-xs text-stone-500">{product.sku} · {product.category}</div>
                </div>
                <span className={`rounded-full px-3 py-1 text-xs font-semibold ${product.stock === 0 ? 'bg-red-100 text-red-800' : 'bg-amber-100 text-amber-800'}`}>
                  Stock : {product.stock} / seuil {product.lowStockThreshold ?? overview.stock.lowStockThreshold ?? 3}
                </span>
              </div>
            </a>
          ))}
        </div>
      )}
    </div>
  </section>
);

export const OperationsExports = ({ exportLabels }: { exportLabels: Record<string, string> }) => (
  <section className={cardClass}>
    <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h2 className="text-lg font-semibold text-brand-900">Exports CSV</h2>
        <p className="text-sm text-stone-500">Télécharge les données pour contrôle, comptabilité ou reporting.</p>
      </div>
    </div>
    <div className="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
      {['orders', 'customers', 'products', 'quotes', 'refunds', 'support'].map((resource) => (
        <a key={resource} className={secondaryActionClass + ' text-center'} href={`/api/admin/operations/exports/${resource}.csv`}>
          Export {exportLabels[resource]}
        </a>
      ))}
    </div>
  </section>
);

export const StatCard = ({ label, value, helper, tone = 'neutral' }: {
  label: string;
  value: number;
  helper: string;
  tone?: 'neutral' | 'warning' | 'danger';
}) => {
  const toneClass = {
    neutral: 'border-brand-100 bg-white text-brand-900',
    warning: 'border-amber-200 bg-amber-50 text-amber-950',
    danger: 'border-red-200 bg-red-50 text-red-950',
  }[tone];

  return (
    <div className={`rounded-2xl border p-5 shadow-sm ${toneClass}`}>
      <div className="text-sm font-medium opacity-75">{label}</div>
      <div className="mt-2 text-3xl font-semibold">{value}</div>
      <p className="mt-2 text-xs leading-5 opacity-75">{helper}</p>
    </div>
  );
};

export const ActionCard = ({ title, description, warning, children }: {
  title: string;
  description: string;
  warning?: string;
  children: ReactNode;
}) => (
  <div className={cardClass}>
    <div className="mb-4">
      <h2 className="text-lg font-semibold text-brand-900">{title}</h2>
      <p className="mt-1 text-sm leading-6 text-stone-600">{description}</p>
      {warning && (
        <p className="mt-3 rounded-2xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs leading-5 text-amber-900">
          {warning}
        </p>
      )}
    </div>
    <div className="space-y-4">{children}</div>
  </div>
);

export const Field = ({ label, helper, className = '', children }: {
  label: string;
  helper?: string;
  className?: string;
  children: ReactNode;
}) => (
  <label className={`block ${className}`}>
    <span className="text-sm font-medium text-stone-700">{label}</span>
    <span className="mt-1 block">{children}</span>
    {helper && <span className="mt-1 block text-xs leading-5 text-stone-500">{helper}</span>}
  </label>
);

export const List = ({ title, items }: {
  title: string;
  items: Array<{ key: string | number; title: string; meta: string; action?: ReactNode }>;
}) => (
  <div className={cardClass}>
    <h2 className="text-lg font-semibold">{title}</h2>
    <div className="mt-4 space-y-3">
      {items.length === 0 ? (
        <p className="text-sm text-stone-500">Aucun élément.</p>
      ) : items.map((item) => (
        <div key={item.key} className="rounded-xl border border-brand-100 bg-brand-50 p-3">
          <div className="font-medium text-brand-900">{item.title}</div>
          <div className="mt-1 text-sm text-stone-500">{item.meta}</div>
          {item.action && <div className="mt-3">{item.action}</div>}
        </div>
      ))}
    </div>
  </div>
);
